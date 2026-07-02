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
 * ⚠ CUOTA DE API: Jimi limita las peticiones diarias por app_key (error
 *   "Illegal access, request frequency is too high today!") y la cuota es
 *   compartida con el resto de la API (incluido el video en vivo). Por eso
 *   este comando NO llama track.list a ciegas cada corrida:
 *
 *   1. Lee el estado de los devices desde el cache de location.list que deja
 *      jimi:sync-positions (jimi:locations:cache) — 0 llamadas extra.
 *   2. Solo pide track.list si el gpsTime del device avanzó respecto a lo ya
 *      sincronizado (jimi:trackedto), con un mínimo de --throttle segundos
 *      entre llamadas por device (default 300).
 *   3. Además hace un barrido completo por device cada --sweep segundos
 *      (default 1800) para capturar tramas buffer que Jimi recibió tarde y
 *      que no mueven el gpsTime de location.list.
 *
 *   Consumo estimado con 10 devices: ~500-2000 llamadas/día según movimiento
 *   (vs 14,400 si se llamara cada minuto por device).
 *
 * Dedup: claves Redis jimi:seen:{imei}:{fixTime} compartidas con
 * jimi:sync-positions. NO es monotónica a propósito: una trama buffer con
 * hora antigua sigue siendo válida y debe guardarse.
 *
 * Uso:
 *   php artisan jimi:sync-tracks
 *   php artisan jimi:sync-tracks --imei=862798052210735 --force
 *   php artisan jimi:sync-tracks --window=10080 --force   (backfill 7 días)
 *
 * Schedule (cada minuto — el throttle interno controla el gasto de API):
 *   $schedule->command('jimi:sync-tracks')->everyMinute()->withoutOverlapping();
 */
class SyncJimiTracks extends Command
{
    protected $signature = 'jimi:sync-tracks
                            {--window=180 : Minutos hacia atrás a consultar (máx 7 días según API)}
                            {--throttle=300 : Segundos mínimos entre llamadas track.list por device}
                            {--sweep=1800 : Segundos entre barridos completos por device (captura tramas buffer)}
                            {--imei= : Sincronizar solo este IMEI}
                            {--force : Ignorar throttle y sweep, consultar siempre}';

    protected $description = 'Sincroniza el historial de tramas GPS desde Jimi (track.list) hacia PositionsStack';

    /** @var int TTL de las claves de dedup jimi:seen (debe superar la ventana) */
    const SEEN_TTL = 172800; // 2 días

    /** @var int Solapamiento hacia atrás sobre lo ya sincronizado (ms) */
    const OVERLAP_MS = 600000; // 10 min

    /**
     * Circuit breaker global al detectar el error de cuota de Jimi
     * ("request frequency is too high"). Mientras exista la clave, los
     * comandos de sync no hacen NINGUNA llamada, para no seguir gastando
     * cuota (que comparte el video en vivo) ni llenar los logs.
     */
    const COOLDOWN_KEY = 'jimi:quota-cooldown';
    const COOLDOWN_TTL = 900; // 15 min

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
        $windowMin   = max(1, min((int) $this->option('window'), 7 * 24 * 60));
        $throttleSec = max(0, (int) $this->option('throttle'));
        $sweepSec    = max(60, (int) $this->option('sweep'));
        $force       = (bool) $this->option('force');

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

        $redis = Redis::connection('process');

        if ($redis->exists(self::COOLDOWN_KEY)) {
            $this->warn('[Jimi] En pausa por límite de cuota de la API (quota-cooldown activo).');
            return self::SUCCESS;
        }

        // Estado actual de los devices según location.list (sin llamadas extra:
        // usa el cache que deja jimi:sync-positions cada minuto)
        $locationsByImei = $this->getLocationsByImei($redis);

        // end_time debe ser anterior a la hora actual (requisito de la API)
        $end    = Carbon::now('UTC')->subMinute();
        $endMs  = $end->getTimestamp() * 1000;
        $endStr = $end->format('Y-m-d H:i:s');

        $stack   = new PositionsStack();
        $total   = 0;
        $skipped = 0;
        $queried = 0;
        $errors  = 0;

        foreach ($devices as $device) {
            $imei        = $device->imei;
            $loc         = $locationsByImei[$imei] ?? null;
            $lastTracked = (int) ($redis->get('jimi:trackedto:' . $imei) ?: 0);

            // --- ¿Hace falta llamar track.list para este device? ---
            $reason = null;

            if ($force) {
                $reason = 'force';
            } elseif ($loc) {
                $fixTime = $this->gpsService->parseTimestampMs((string) ($loc['gpsTime'] ?? ''));
                if ($fixTime > $lastTracked) {
                    $reason = 'new'; // hay tramas más nuevas que lo sincronizado
                }
            }

            // Barrido periódico: captura tramas buffer que Jimi recibió tarde
            // (tienen gpsTime viejo, no mueven la "última posición" de location.list)
            if (!$reason && !$redis->exists('jimi:sweep:' . $imei)) {
                $reason = 'sweep';
            }

            if (!$reason) {
                continue;
            }

            // Throttle por device: proteger la cuota diaria de la API
            if (!$force && $throttleSec > 0 && $redis->exists('jimi:trackthrottle:' . $imei)) {
                continue;
            }

            // Ventana a consultar: desde lo ya sincronizado (con solapamiento)
            // o la ventana completa si es barrido / primera vez
            $windowStartMs = $endMs - $windowMin * 60000;
            $beginMs       = ($reason === 'new' && $lastTracked > 0)
                ? max($windowStartMs, $lastTracked - self::OVERLAP_MS)
                : $windowStartMs;
            $beginStr = Carbon::createFromTimestampMs($beginMs, 'UTC')->format('Y-m-d H:i:s');

            // Throttle ANTES de llamar (aunque la llamada falle): un error no
            // debe provocar reintentos cada minuto contra la cuota de la API
            if ($throttleSec > 0) {
                $redis->setex('jimi:trackthrottle:' . $imei, $throttleSec, 1);
            }

            $points = $this->fetchTracks($imei, $beginStr, $endStr);
            $queried++;

            if ($points === null) {
                $errors++;
                if ($redis->exists(self::COOLDOWN_KEY)) {
                    $this->warn('[Jimi] Cuota de API agotada — abortando el resto de la corrida.');
                    break;
                }
                continue;
            }

            if ($reason !== 'new') {
                $redis->setex('jimi:sweep:' . $imei, $sweepSec, 1);
            }

            $maxFixTime = $lastTracked;
            $lastLat    = null;
            $lastLng    = null;

            foreach ($points as $point) {
                if (!is_array($point)) {
                    continue;
                }

                $data = $this->gpsService->mapTrackToPositionData($point, $imei, $device->jimi_model);

                if ($data['fixTime'] <= 0) {
                    continue;
                }

                if ($data['fixTime'] > $maxFixTime) {
                    $maxFixTime = $data['fixTime'];
                    $lastLat    = $data['latitude'];
                    $lastLng    = $data['longitude'];
                }

                // Dedup exacto por (imei, fixTime), compartido con jimi:sync-positions
                $seenKey = 'jimi:seen:' . $imei . ':' . $data['fixTime'];
                if (!$redis->setnx($seenKey, 1)) {
                    $skipped++;
                    continue;
                }
                $redis->expire($seenKey, self::SEEN_TTL);

                $stack->add($data);
                $total++;
            }

            if ($maxFixTime > $lastTracked) {
                $redis->setex('jimi:trackedto:' . $imei, 604800, $maxFixTime); // 7 días

                // Última posición GPS confiable — la usa jimi:sync-positions
                // para descartar heartbeats con coordenadas viejas
                if ($lastLat !== null) {
                    $redis->setex(
                        'jimi:lastpos:' . $imei,
                        604800,
                        json_encode(['fixTime' => $maxFixTime, 'lat' => $lastLat, 'lng' => $lastLng])
                    );
                }
            }
        }

        $this->info("[Jimi] Llamadas track.list: {$queried}/{$devices->count()}, tramas encoladas: {$total}, ya vistas: {$skipped}, errores: {$errors}");
        Log::info('[Jimi] jimi:sync-tracks completado', [
            'llamadas'  => $queried,
            'devices'   => $devices->count(),
            'encoladas' => $total,
            'ya_vistas' => $skipped,
            'errores'   => $errors,
        ]);

        return $errors > 0 && $total === 0 && $queried > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Estado actual de los devices según location.list, indexado por IMEI.
     *
     * Usa el cache jimi:locations:cache (escrito por jimi:sync-positions cada
     * minuto). Si no existe, hace UNA llamada a location.list. Si también
     * falla, devuelve [] y la decisión queda en manos del barrido periódico.
     *
     * @return array<string, array>
     */
    private function getLocationsByImei(\Illuminate\Redis\Connections\Connection $redis): array
    {
        $locations = null;

        $cached = $redis->get('jimi:locations:cache');
        if ($cached) {
            $decoded = json_decode($cached, true);
            if (is_array($decoded)) {
                $locations = $decoded;
            }
        }

        if ($locations === null) {
            try {
                $locations = $this->gpsService->getLocationsByAccount(config('jimi.account'));
            } catch (JimiException $e) {
                Log::warning('[Jimi] sync-tracks: sin cache ni location.list', ['error' => $e->getMessage()]);
                return [];
            }
        }

        $byImei = [];
        foreach ($locations as $loc) {
            if (is_array($loc) && !empty($loc['imei'])) {
                $byImei[$loc['imei']] = $loc;
            }
        }

        return $byImei;
    }

    /**
     * Obtiene el track de un IMEI.
     *
     * - Error de token → renueva y reintenta UNA vez.
     * - Error de cuota (rate limit) → activa el cooldown global y NO reintenta
     *   (cada reintento gasta más cuota, que comparte el video en vivo).
     * - Otros errores → no reintenta.
     *
     * @return array[]|null  null si falla
     */
    private function fetchTracks(string $imei, string $beginTime, string $endTime): ?array
    {
        try {
            return $this->gpsService->getTrackList($imei, $beginTime, $endTime);
        } catch (JimiException $e) {
            if ($e->isRateLimit()) {
                Redis::connection('process')->setex(self::COOLDOWN_KEY, self::COOLDOWN_TTL, 1);
                Log::warning('[Jimi] Límite de cuota de API alcanzado — pausa global de ' . self::COOLDOWN_TTL . 's', [
                    'imei'  => $imei,
                    'error' => $e->getMessage(),
                ]);
                $this->error("[Jimi] {$imei}: " . $e->getMessage());
                return null;
            }

            if (!$e->isTokenError()) {
                Log::error('[Jimi] Error al obtener track', [
                    'imei'  => $imei,
                    'error' => $e->getMessage(),
                    'code'  => $e->getCode(),
                ]);
                $this->error("[Jimi] {$imei}: " . $e->getMessage());
                return null;
            }

            // Token inválido/expirado → renovar y reintentar una vez
            Log::warning('[Jimi] Token inválido al obtener track, renovando...', [
                'imei'  => $imei,
                'error' => $e->getMessage(),
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
