<?php

namespace App\Console\Commands;

use App\Console\PositionsStack;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tobuli\Entities\Device;
use Tobuli\Services\Jimi\JimiAuthService;
use Tobuli\Services\Jimi\JimiException;
use Tobuli\Services\Jimi\JimiGpsService;

/**
 * Sincroniza el HISTORIAL de tramas GPS desde Jimi (jimi.device.track.list)
 * hacia el sistema via PositionsStack.
 *
 * ⚠ Esta es la fuente de verdad de las posiciones GPS. A diferencia de
 *   location.list (que solo entrega la última posición conocida y a veces
 *   mezcla gpsTime nuevo con coordenadas viejas), track.list devuelve TODAS
 *   las tramas almacenadas — incluidas las buffereadas que el equipo sube
 *   tarde — con sus coordenadas correctas. Es lo mismo que dibuja TrackSolid.
 *
 * Estrategia: ventana deslizante. Cada corrida consulta las últimas N horas
 * (--window, default 180 min) por dispositivo y descarta las tramas ya vistas
 * con claves Redis jimi:seen:{imei}:{fixTime} (compartidas con
 * jimi:sync-positions). Así una trama buffer que Jimi reciba tarde entra en
 * cuanto aparece en el historial, sin duplicar las anteriores.
 *
 * Uso:
 *   php artisan jimi:sync-tracks
 *   php artisan jimi:sync-tracks --window=360 --imei=868120145233604
 *
 * Schedule (cada minuto):
 *   $schedule->command('jimi:sync-tracks')->everyMinute()->withoutOverlapping();
 */
class SyncJimiTracks extends Command
{
    protected $signature = 'jimi:sync-tracks
                            {--window=180 : Minutos hacia atrás a consultar (máx 7 días según API)}
                            {--imei= : Sincronizar solo este IMEI}';

    protected $description = 'Sincroniza el historial de tramas GPS desde Jimi (track.list) hacia PositionsStack';

    /** @var int TTL de las claves de dedup jimi:seen (debe superar la ventana) */
    const SEEN_TTL = 172800; // 2 días

    protected JimiGpsService $gpsService;
    protected JimiAuthService $authService;

    public function __construct(JimiGpsService $gpsService, JimiAuthService $authService)
    {
        parent::__construct();

        $this->gpsService  = $gpsService;
        $this->authService = $authService;
    }

    public function handle(): int
    {
        $windowMin = max(1, min((int) $this->option('window'), 7 * 24 * 60));

        $query = Device::jimi()
            ->whereNotNull('imei')
            ->where('imei', '!=', '');

        if ($imei = $this->option('imei')) {
            $query->where('imei', $imei);
        }

        $devices = $query->get(['id', 'imei', 'jimi_model']);

        if ($devices->isEmpty()) {
            $this->warn('[Jimi] No hay dispositivos configurados con jimi_type.');
            return self::SUCCESS;
        }

        // end_time debe ser anterior a la hora actual (requisito de la API)
        $end   = Carbon::now('UTC')->subMinute();
        $begin = $end->copy()->subMinutes($windowMin);

        $beginStr = $begin->format('Y-m-d H:i:s');
        $endStr   = $end->format('Y-m-d H:i:s');

        $stack   = new PositionsStack();
        $redis   = Redis::connection('process');
        $total   = 0;
        $skipped = 0;
        $errors  = 0;

        foreach ($devices as $device) {
            $points = $this->fetchTracks($device->imei, $beginStr, $endStr);

            if ($points === null) {
                $errors++;
                continue;
            }

            $lastFixTime = 0;
            $lastLat     = null;
            $lastLng     = null;

            foreach ($points as $point) {
                if (!is_array($point)) {
                    continue;
                }

                $data = $this->gpsService->mapTrackToPositionData($point, $device->imei, $device->jimi_model);

                if ($data['fixTime'] <= 0) {
                    continue;
                }

                // Rastrear la trama más reciente del lote (para jimi:lastpos)
                if ($data['fixTime'] > $lastFixTime) {
                    $lastFixTime = $data['fixTime'];
                    $lastLat     = $data['latitude'];
                    $lastLng     = $data['longitude'];
                }

                // Dedup exacto por (imei, fixTime), compartido con jimi:sync-positions.
                // NO es monotónico a propósito: una trama buffer con hora antigua
                // que Jimi recibe tarde sigue siendo válida y debe guardarse.
                $seenKey = 'jimi:seen:' . $data['imei'] . ':' . $data['fixTime'];
                if (!$redis->setnx($seenKey, 1)) {
                    $skipped++;
                    continue;
                }
                $redis->expire($seenKey, self::SEEN_TTL);

                $stack->add($data);
                $total++;
            }

            // Última posición GPS confiable del device — la usa jimi:sync-positions
            // para descartar heartbeats con coordenadas viejas de location.list.
            if ($lastFixTime > 0) {
                $redis->setex(
                    'jimi:lastpos:' . $device->imei,
                    604800, // 7 días
                    json_encode(['fixTime' => $lastFixTime, 'lat' => $lastLat, 'lng' => $lastLng])
                );
            }
        }

        $this->info("[Jimi] Tramas encoladas: {$total}, ya vistas: {$skipped}, dispositivos con error: {$errors}");
        Log::info('[Jimi] jimi:sync-tracks completado', [
            'encoladas'   => $total,
            'ya_vistas'   => $skipped,
            'errores'     => $errors,
            'ventana_min' => $windowMin,
            'begin'       => $beginStr,
            'end'         => $endStr,
        ]);

        return $errors > 0 && $total === 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Obtiene el track de un IMEI con reintento automático si el token expiró.
     *
     * @return array[]|null  null si falla definitivamente
     */
    private function fetchTracks(string $imei, string $beginTime, string $endTime): ?array
    {
        try {
            return $this->gpsService->getTrackList($imei, $beginTime, $endTime);
        } catch (JimiException $e) {
            Log::warning('[Jimi] Error al obtener track, renovando token...', [
                'imei'  => $imei,
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
            ]);

            $this->authService->forgetToken();

            try {
                return $this->gpsService->getTrackList($imei, $beginTime, $endTime);
            } catch (JimiException $e2) {
                Log::error('[Jimi] Error definitivo al obtener track', [
                    'imei'  => $imei,
                    'error' => $e2->getMessage(),
                ]);
                $this->error("[Jimi] {$imei}: " . $e2->getMessage());

                return null;
            }
        }
    }
}
