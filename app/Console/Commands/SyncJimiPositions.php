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
 * Sincroniza posiciones GPS desde Jimi IoT hacia el sistema existente via PositionsStack.
 *
 * ⚠ NO escribe directamente en DB. Usa PositionsStack (Redis) como pasarela
 *   para que el daemon PositionsWriter procese las posiciones normalmente.
 *
 * Flujo:
 *   1. Agrupa los devices locales por jimi_account (o cuenta raíz si es null)
 *   2. Para cada cuenta, consulta posiciones (jimi.user.device.location.list)
 *   3. Filtra solo IMEIs del grupo de esa cuenta
 *   4. Mapea y encola en Redis → PositionsWriter → traccar DB → eventos
 *
 * Uso:
 *   php artisan jimi:sync-positions
 *
 * Schedule recomendado (cada minuto):
 *   $schedule->command('jimi:sync-positions')->everyMinute()->withoutOverlapping();
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
        $total   = 0;
        $skipped = 0;
        $errors  = 0;

        $locations = $this->fetchLocationsByAccount($root);

        if ($locations === null) {
            $this->error('[Jimi] No se pudieron obtener posiciones de la cuenta raíz.');
            return self::FAILURE;
        }

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

            // --- Posición GPS principal ---
            if ($data['fixTime'] > 0) {
                $redisKey = 'jimi:lastfix:' . $data['imei'];
                $lastFix  = $redis->get($redisKey);
                if ($lastFix !== null && (int) $lastFix === $data['fixTime']) {
                    $skipped++;
                } else {
                    $redis->setex($redisKey, 3600, $data['fixTime']); // TTL 1h
                    $stack->add($data);
                    $total++;
                }
            }

            // --- Posición heartbeat (hbTime > gpsTime) ---
            $hbData = $this->gpsService->mapToHbPositionData($location);
            if ($hbData && $hbData['fixTime'] > 0) {
                $hbKey  = 'jimi:lasthb:' . $hbData['imei'];
                $lastHb = $redis->get($hbKey);
                if ($lastHb !== null && (int) $lastHb === $hbData['fixTime']) {
                    $skipped++;
                } else {
                    $redis->setex($hbKey, 3600, $hbData['fixTime']); // TTL 1h
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
            // Token inválido/expirado → renovar y reintentar una vez
            Log::warning('[Jimi] Error al obtener posiciones, renovando token...', [
                'account' => $account,
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
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
