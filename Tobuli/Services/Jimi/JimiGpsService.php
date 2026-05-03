<?php

namespace Tobuli\Services\Jimi;

use Illuminate\Support\Facades\Log;

/**
 * Servicio GPS de Jimi IoT.
 *
 * Obtiene posiciones GPS desde la plataforma Jimi y las convierte
 * al formato que acepta App\Console\PositionsStack::add().
 *
 * ⚠ NO guarda directamente en DB. Usa PositionsStack (Redis) como pasarela.
 *
 * Flujo completo:
 *   JimiGpsService::getLocations*() → mapToPositionData() → PositionsStack::add()
 *   → PositionsWriter (daemon) → traccar DB → eventos del sistema
 *
 * Método Jimi: jimi.user.device.location.list
 */
class JimiGpsService
{
    protected JimiClient $client;
    protected JimiAuthService $auth;

    public function __construct(JimiClient $client, JimiAuthService $auth)
    {
        $this->client = $client;
        $this->auth   = $auth;
    }

    /**
     * Obtiene las últimas posiciones de TODOS los dispositivos de la cuenta raíz.
     *
     * Método: jimi.user.device.location.list
     * Param:  target = account de Jimi
     *
     * ⚠ Solo devuelve dispositivos del nivel de cuenta indicado.
     *   Para incluir subcuentas usa getLocationsByAccountRecursive().
     *
     * @param  string|null $account  Cuenta Jimi (null = config('jimi.account'))
     * @return array[]
     */
    public function getLocationsByAccount(?string $account = null): array
    {
        $result = $this->client->send('jimi.user.device.location.list', [
            'access_token' => $this->auth->getAccessToken(),
            'target'       => $account ?? config('jimi.account'),
        ]);

        return is_array($result) ? $result : [];
    }

    /**
     * Obtiene posiciones de TODOS los dispositivos: cuenta raíz + subcuentas.
     *
     * Requiere JimiDeviceService para listar subcuentas.
     * Itera cada nivel y combina los resultados en una lista plana.
     *
     * @param  JimiDeviceService $deviceService
     * @param  string|null       $rootAccount   Cuenta raíz (null = config('jimi.account'))
     * @return array[]                          Lista plana de posiciones con campo '_account'
     */
    public function getLocationsByAccountRecursive(JimiDeviceService $deviceService, ?string $rootAccount = null): array
    {
        $account = $rootAccount ?? config('jimi.account');
        $all     = [];

        $this->collectLocationsRecursive($deviceService, $account, $all, 0, 5);

        return $all;
    }

    /**
     * @internal
     */
    private function collectLocationsRecursive(
        JimiDeviceService $deviceService,
        string $account,
        array &$all,
        int $depth,
        int $maxDepth
    ): void {
        // Posiciones de dispositivos directos de esta cuenta
        $locations = $this->getLocationsByAccount($account);
        foreach ($locations as &$loc) {
            $loc['_account'] = $account;
        }
        unset($loc);

        $all = array_merge($all, $locations);

        if ($depth < $maxDepth) {
            $subAccounts = $deviceService->listSubAccounts($account);
            foreach ($subAccounts as $sub) {
                $subName = $sub['account'] ?? null;
                if ($subName && $subName !== $account) {
                    $this->collectLocationsRecursive($deviceService, $subName, $all, $depth + 1, $maxDepth);
                }
            }
        }
    }

    /**
     * Obtiene posiciones de IMEIs específicos.
     *
     * Método: jimi.user.device.location.list
     * Param:  imeis = lista separada por coma
     *
     * @param  string[] $imeis  Array de IMEIs a consultar
     * @return array[]          Lista de posiciones GPS
     */
    public function getLocationsByImeis(array $imeis): array
    {
        if (empty($imeis)) {
            return [];
        }

        $result = $this->client->send('jimi.user.device.location.list', [
            'access_token' => $this->auth->getAccessToken(),
            'imeis'        => implode(',', $imeis),
        ]);

        return is_array($result) ? $result : [];
    }

    /**
     * Convierte una posición del formato Jimi al formato de PositionsStack.
     *
     * Campos reales de jimi.user.device.location.list (verificado):
     *   imei        → imei
     *   gpsTime     → fixTime (ms)  — null si sin señal
     *   lat         → latitude      — 0 si sin señal
     *   lng         → longitude     — 0 si sin señal
     *   speed       → speed         — null si sin señal
     *   direction   → course
     *   accStatus   → attributes.ignition  ("0"=off, "1"=on)
     *   status      → attributes.status    ("0"=offline, etc.)
     *   hbTime      → fallback de fixTime si gpsTime es null
     *
     * @param  array $location  Elemento de la respuesta de jimi.user.device.location.list
     * @return array            Formato listo para PositionsStack::add()
     */
    public function mapToPositionData(array $location): array
    {
        Log::info('[JimiGps] Raw position recibida', [
            'imei'      => $location['imei']    ?? null,
            'gpsTime'   => $location['gpsTime'] ?? null,
            'hbTime'    => $location['hbTime']  ?? null,
            'lat'       => $location['lat']     ?? null,
            'lng'       => $location['lng']     ?? null,
            'speed'     => $location['speed']   ?? null,
            'mcType'    => $location['mcType']  ?? null,
            'accStatus' => $location['accStatus'] ?? null,
            'raw'       => $location,
        ]);
        // Usar gpsTime; si es null, usar hbTime como fallback
        $timeStr = $location['gpsTime'] ?? $location['hbTime'] ?? '';

        return [
            'imei'       => $location['imei'] ?? '',
            'fixTime'    => $this->parseTimestampMs((string) $timeStr),
            'valid'      => !empty($location['gpsTime']), // inválido si no tiene fix GPS
            'latitude'   => (float) ($location['lat']   ?? 0),
            'longitude'  => (float) ($location['lng']   ?? 0),
            'speed'      => round((float) ($location['speed'] ?? 0) * 0.539957, 4), // km/h → knots
            'altitude'   => 0.0,
            'course'     => (float) ($location['direction'] ?? 0),
            'protocol'   => $location['mcType'] ?? 'jimi',
            'attributes' => array_filter([
                'ignition'  => ($location['accStatus'] ?? '0') === '1',
                'status'    => $location['status']      ?? null,
                'signal'    => $location['gpsSignal']   ?? null,
                'battery'   => $location['batteryPowerVal'] ?? $location['electQuantity'] ?? null,
                'power'     => $location['powerValue']  ?? null,
                'sat'       => $location['gpsNum']      ?? null,
                'posType'   => $location['posType']     ?? null,
                'distance'  => isset($location['distance'])      && $location['distance']      !== null && $location['distance']      !== '' ? (float) $location['distance']      : null,
                'odometer'  => isset($location['currentMileage']) && $location['currentMileage'] !== null && $location['currentMileage'] !== '' ? (float) $location['currentMileage'] : null,
                'iccid'     => isset($location['iccid']) && $location['iccid'] !== null && $location['iccid'] !== '' ? $location['iccid'] : null,
            ], fn($v) => $v !== null),
        ];
    }

    /**
     * Devuelve la posición heartbeat (hbTime) si es más reciente que gpsTime.
     * Útil para mantener el dispositivo "vivo" en el mapa aunque el fix GPS sea antiguo.
     *
     * @param  array $location  Elemento de la respuesta de jimi.user.device.location.list
     * @return array|null       Posición heartbeat, o null si no aplica
     */
    public function mapToHbPositionData(array $location): ?array
    {
        $gpsTimeStr = $location['gpsTime'] ?? null;
        $hbTimeStr  = $location['hbTime']  ?? null;

        if (!$gpsTimeStr || !$hbTimeStr) {
            return null;
        }

        $gpsTs = $this->parseTimestampMs($gpsTimeStr);
        $hbTs  = $this->parseTimestampMs($hbTimeStr);

        if ($hbTs <= $gpsTs) {
            return null;
        }

        return [
            'imei'       => $location['imei'] ?? '',
            'fixTime'    => $hbTs,
            'valid'      => true,
            'latitude'   => (float) ($location['lat']       ?? 0),
            'longitude'  => (float) ($location['lng']       ?? 0),
            'speed'      => round((float) ($location['speed'] ?? 0) * 0.539957, 4),
            'altitude'   => 0.0,
            'course'     => (float) ($location['direction'] ?? 0),
            'protocol'   => $location['mcType'] ?? 'jimi',
            'attributes' => array_filter([
                'ignition'  => ($location['accStatus'] ?? '0') === '1',
                'status'    => $location['status']      ?? null,
                'signal'    => $location['gpsSignal']   ?? null,
                'battery'   => $location['batteryPowerVal'] ?? $location['electQuantity'] ?? null,
                'power'     => $location['powerValue']  ?? null,
                'sat'       => $location['gpsNum']      ?? null,
                'posType'   => 'HB',
                'distance'  => isset($location['distance'])       && $location['distance']       !== null && $location['distance']       !== '' ? (float) $location['distance']       : null,
                'odometer'  => isset($location['currentMileage']) && $location['currentMileage'] !== null && $location['currentMileage'] !== '' ? (float) $location['currentMileage'] : null,
                'iccid'     => isset($location['iccid'])          && $location['iccid']          !== null && $location['iccid']          !== '' ? $location['iccid']          : null,
            ], fn($v) => $v !== null),
        ];
    }

    /**
     * Parsea una fecha/hora de Jimi (UTC) a timestamp en milisegundos.
     * Las fechas de la API Jimi ya vienen en UTC — no se aplica conversión de zona.
     *
     * @param  string $datetime  Formato "Y-m-d H:i:s" (UTC) o timestamp UNIX en segundos
     * @return int               Timestamp en milisegundos (UTC)
     */
    private function parseTimestampMs(string $datetime): int
    {
        if (empty($datetime)) {
            return (int) (microtime(true) * 1000);
        }

        // Si ya es numérico, asumir timestamp UNIX en segundos
        if (is_numeric($datetime)) {
            return (int) $datetime * 1000;
        }

        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $datetime, 'UTC')
                ->getTimestamp() * 1000;
        } catch (\Throwable $e) {
            Log::warning('[JimiGps] No se pudo parsear fecha', ['datetime' => $datetime, 'error' => $e->getMessage()]);
            return (int) (microtime(true) * 1000);
        }
    }
}
