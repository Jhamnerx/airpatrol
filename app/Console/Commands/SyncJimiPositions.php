<?php

namespace App\Console\Commands;

use App\Console\PositionsStack;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Tobuli\Entities\Device;
use Tobuli\Services\Jimi\JimiAuthService;
use Tobuli\Services\Jimi\JimiException;
use Tobuli\Services\Jimi\JimiGpsService;

/**
 * Sincroniza el ESTADO en vivo de los devices Jimi via location.list.
 *
 * ⚠ La fuente de verdad de las tramas GPS es jimi:sync-tracks (track.list).
 *   Este comando queda como complemento:
 *   - Refresca jimi_model (mcType) de los devices
 *   - Inserta heartbeats (hbTime) para mantener el device "vivo" en el mapa
 *   - Respaldo: inserta tramas GPS de >5 min que el track sync no guardó
 *   location.list solo entrega la última posición conocida y a veces mezcla
 *   gpsTime nuevo con coordenadas viejas — por eso NO se usa como fuente
 *   principal de posiciones.
 *
 * ⚠ NO escribe directamente en DB. Usa PositionsStack (Redis) como pasarela
 *   para que el daemon PositionsWriter procese las posiciones normalmente.
 *
 * Uso:
 *   php artisan jimi:sync-positions
 *
 * Schedule recomendado (cada minuto):
 *   $schedule->command('jimi:sync-positions')->everyMinute()->withoutOverlapping();
 *
 * @see App\Console\Commands\SyncJimiTracks  Fuente principal de tramas GPS
 */
class SyncJimiPositions extends Command
{
    protected $signature   = 'jimi:sync-positions';
    protected $description = 'Sincroniza posiciones GPS desde Jimi IoT hacia PositionsStack (Redis)';

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
        // 1. Dispositivos Jimi locales — todos usan la cuenta raíz
        $root    = config('jimi.account');
        $devices = Device::jimi()
            ->whereNotNull('imei')
            ->where('imei', '!=', '')
            ->get(['id', 'imei', 'jimi_model']);

        if ($devices->isEmpty()) {
            $this->warn('[Jimi] No hay dispositivos configurados con jimi_type.');
            return self::SUCCESS;
        }

        // Mapa imei → device para lookup O(1) y para refrescar jimi_model
        $deviceByImei = $devices->keyBy('imei');
        $imeiSet      = $deviceByImei->all();

        // 2. Pedir posiciones a la cuenta raíz y encolar
        $stack   = new PositionsStack();
        $redis   = Redis::connection('process');

        if ($redis->exists(SyncJimiTracks::COOLDOWN_KEY)) {
            $this->warn('[Jimi] En pausa por límite de cuota de la API (quota-cooldown activo).');
            return self::SUCCESS;
        }
        $total   = 0;
        $skipped = 0;
        $errors  = 0;

        $locations = $this->fetchLocationsByAccount($root);

        if ($locations === null) {
            $this->error('[Jimi] No se pudieron obtener posiciones de la cuenta raíz.');
            return self::FAILURE;
        }

        // Cachear la respuesta cruda para jimi:sync-tracks: así decide qué devices
        // tienen tramas nuevas sin gastar llamadas extra de la cuota diaria de Jimi
        $redis->setex('jimi:locations:cache', 120, json_encode($locations));

        foreach ($locations as $location) {
            $data = $this->gpsService->mapToPositionData($location);

            if (empty($data['imei'])) {
                continue;
            }

            // Solo procesar IMEIs registrados en el sistema local
            if (!array_key_exists($data['imei'], $imeiSet)) {
                continue;
            }

            // Refrescar el modelo (mcType) del equipo si cambió o aún no se conoce.
            // Se necesita para traducir el canal de cámara (Concox=base 0 / JT808=base 1).
            $mcType = $location['mcType'] ?? null;
            if ($mcType) {
                $device = $deviceByImei->get($data['imei']);
                if ($device && $device->jimi_model !== $mcType) {
                    $device->forceFill(['jimi_model' => $mcType])->saveQuietly();
                }
            }

            // --- Posición GPS principal (solo como RESPALDO de jimi:sync-tracks) ---
            // La fuente de verdad de las tramas GPS es jimi:sync-tracks (track.list):
            // location.list solo entrega la última posición conocida y a veces mezcla
            // gpsTime nuevo con coordenadas viejas (dibuja saltos/líneas rectas).
            // Por eso aquí solo se insertan tramas con más de 5 min de antigüedad que
            // el track sync no haya guardado (claves jimi:seen compartidas): si
            // track.list funciona, ya las insertó con coordenadas correctas y esto
            // no hace nada; si está caído, el sistema sigue recibiendo posiciones
            // con ~5 min de retraso.
            $ageMs = (int) (microtime(true) * 1000) - $data['fixTime'];
            if ($data['fixTime'] > 0 && $ageMs >= 300000) {
                $redisKey = 'jimi:lastfix:' . $data['imei'];
                $lastFix  = $redis->get($redisKey);
                // Dedup monotónica: location.list nunca entrega tramas antiguas
                // válidas, así que todo fixTime <= al último se descarta. (El TTL
                // corto anterior con igualdad exacta reinsertaba la misma trama
                // cada hora con el vehículo parado.)
                if ($lastFix !== null && (int) $lastFix >= $data['fixTime']) {
                    $skipped++;
                } else {
                    $redis->setex($redisKey, 604800, $data['fixTime']); // TTL 7 días

                    $seenKey = 'jimi:seen:' . $data['imei'] . ':' . $data['fixTime'];
                    if ($redis->setnx($seenKey, 1)) {
                        $redis->expire($seenKey, 172800); // 2 días
                        $stack->add($data);
                        $total++;
                    } else {
                        $skipped++; // ya la insertó jimi:sync-tracks
                    }
                }
            }

            // --- Posición heartbeat (hbTime > gpsTime) ---
            // Mantiene el device "vivo" en el mapa cuando manda heartbeats sin fix
            // GPS nuevo (normalmente parado). Se descarta si sus coordenadas no
            // coinciden con la última posición GPS confiable (jimi:lastpos, escrita
            // por jimi:sync-tracks): significa que location.list trae coordenadas
            // atrasadas y guardarlas dibujaría un punto falso.
            $hbData = $this->gpsService->mapToHbPositionData($location);
            if ($hbData && $hbData['fixTime'] > 0) {
                $lastPosRaw = $redis->get('jimi:lastpos:' . $hbData['imei']);
                $lastPos    = $lastPosRaw ? json_decode($lastPosRaw, true) : null;
                $staleCoords = is_array($lastPos)
                    && (abs((float) $lastPos['lat'] - $hbData['latitude']) > 0.001
                        || abs((float) $lastPos['lng'] - $hbData['longitude']) > 0.001);

                $hbKey  = 'jimi:lasthb:' . $hbData['imei'];
                $lastHb = $redis->get($hbKey);
                if ($staleCoords || ($lastHb !== null && (int) $lastHb >= $hbData['fixTime'])) {
                    $skipped++;
                } else {
                    $redis->setex($hbKey, 604800, $hbData['fixTime']); // TTL 7 días
                    $stack->add($hbData);
                    $total++;
                }
            }
        }

        $this->info("[Jimi] Posiciones encoladas: {$total}, omitidas (dup): {$skipped} (errores: {$errors})");
        Log::info("[Jimi] jimi:sync-positions completado", [
            'encoladas' => $total,
            'omitidas'  => $skipped,
            'cuenta'    => $root,
            'errores'   => $errors,
        ]);

        return self::SUCCESS;
    }

    /**
     * Obtiene posiciones de una cuenta con reintento automático si el token expiró.
     *
     * @return array[]|null  null si falla definitivamente
     */
    private function fetchLocationsByAccount(string $account): ?array
    {
        try {
            return $this->gpsService->getLocationsByAccount($account);
        } catch (JimiException $e) {
            if ($e->isRateLimit()) {
                // Cuota de API agotada → activar pausa global y NO reintentar
                Redis::connection('process')->setex(SyncJimiTracks::COOLDOWN_KEY, SyncJimiTracks::COOLDOWN_TTL, 1);
                Log::warning('[Jimi] Límite de cuota de API alcanzado — pausa global', [
                    'account' => $account,
                    'error'   => $e->getMessage(),
                ]);
                $this->error("[Jimi] {$account}: " . $e->getMessage());
                return null;
            }

            if (!$e->isTokenError()) {
                Log::error('[Jimi] Error al obtener posiciones', [
                    'account' => $account,
                    'error'   => $e->getMessage(),
                    'code'    => $e->getCode(),
                ]);
                $this->error("[Jimi] {$account}: " . $e->getMessage());
                return null;
            }

            // Token inválido/expirado → renovar y reintentar una vez
            Log::warning('[Jimi] Token inválido al obtener posiciones, renovando...', [
                'account' => $account,
                'error'   => $e->getMessage(),
            ]);

            $this->authService->forgetToken();

            try {
                return $this->gpsService->getLocationsByAccount($account);
            } catch (JimiException $e2) {
                Log::error('[Jimi] Error definitivo al obtener posiciones', [
                    'account' => $account,
                    'error'   => $e2->getMessage(),
                ]);
                $this->error("[Jimi] {$account}: " . $e2->getMessage());

                return null;
            }
        }
    }
}
