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
     * Obtiene el historial de tramas (track) de UN dispositivo.
     *
     * Método: jimi.device.track.list
     * Params: imei (solo 1), begin_time, end_time ("Y-m-d H:i:s", UTC)
     *
     * Restricciones de la API:
     *   - Rango máximo de 7 días, dentro de los últimos 3 meses
     *   - end_time debe ser anterior a la hora actual
     *
     * A diferencia de location.list (solo última posición), devuelve TODAS
     * las tramas almacenadas, incluidas las buffereadas (gpsMode=1) que el
     * equipo subió tarde. Es la misma fuente que usa el historial de TrackSolid.
     *
     * @param  string $imei
     * @param  string $beginTime  "Y-m-d H:i:s" en UTC
     * @param  string $endTime    "Y-m-d H:i:s" en UTC (anterior a ahora)
     * @return array[]            Lista de puntos de track
     */
    public function getTrackList(string $imei, string $beginTime, string $endTime): array
    {
        $result = $this->client->send('jimi.device.track.list', [
            'access_token' => $this->auth->getAccessToken(),
            'imei'         => $imei,
            'begin_time'   => $beginTime,
            'end_time'     => $endTime,
        ]);

        if (!is_array($result)) {
            return [];
        }

        // Respuesta normal: lista de puntos. Algunas versiones envuelven la
        // lista junto a campos extra (ej. mileage) — buscar la primera lista
        // de puntos válida dentro del resultado.
        if (isset($result[0]) && is_array($result[0])) {
            return $result;
        }

        foreach ($result as $value) {
            if (is_array($value) && isset($value[0]) && is_array($value[0]) && array_key_exists('lat', $value[0])) {
                return $value;
            }
        }

        return [];
    }

    /**
     * Convierte un punto de track (jimi.device.track.list) al formato de PositionsStack.
     *
     * Campos del punto según la doc oficial:
     *   lat, lng     → coordenadas
     *   gpsTime      → fixTime ("Y-m-d H:i:s" UTC)
     *   gpsSpeed     → speed (km/h)
     *   direction    → course
     *   posType      → "1"=GPS, "2"=LBS, "3"=WIFI
     *   satellite    → intensidad de señal GPS
     *   ignition     → "ON" / "OFF"
     *   accStatus    → estado ACC
     *   gpsMode      → 0=tiempo real, 1=retransmitida (buffer)
     *
     * El punto NO incluye imei ni mcType: se pasan desde el contexto del device.
     *
     * @param  array       $point     Punto de la respuesta de jimi.device.track.list
     * @param  string      $imei      IMEI del dispositivo consultado
     * @param  string|null $protocol  Protocolo/modelo (jimi_model del device)
     * @return array                  Formato listo para PositionsStack::add()
     */
    public function mapTrackToPositionData(array $point, string $imei, ?string $protocol = null): array
    {
        $posTypeMap = ['1' => 'GPS', '2' => 'LBS', '3' => 'WIFI'];
        $posType    = $posTypeMap[(string) ($point['posType'] ?? '')] ?? ($point['posType'] ?? null);

        $ignition = null;
        if (isset($point['ignition'])) {
            $ignition = strtoupper((string) $point['ignition']) === 'ON';
        } elseif (isset($point['accStatus'])) {
            // Verificado: accStatus llega como "ON"/"OFF" en track.list
            $ignition = in_array(strtoupper((string) $point['accStatus']), ['ON', '1'], true);
        }

        return [
            'imei'       => $imei,
            'fixTime'    => $this->parseTimestampMs((string) ($point['gpsTime'] ?? '')),
            'valid'      => !empty($point['gpsTime']),
            'latitude'   => (float) ($point['lat'] ?? 0),
            'longitude'  => (float) ($point['lng'] ?? 0),
            'speed'      => round((float) ($point['gpsSpeed'] ?? 0) * 0.539957, 4), // km/h → knots
            'altitude'   => 0.0,
            'course'     => (float) ($point['direction'] ?? 0),
            'protocol'   => $protocol ?: 'jimi',
            'attributes' => array_filter([
                'ignition'   => $ignition,
                'sat'        => $point['satellite']  ?? null,
                'posType'    => $posType,
                'buffered'   => isset($point['gpsMode']) ? ((int) $point['gpsMode'] === 1) : null,
                'confidence' => $point['confidence'] ?? null,
            ], fn($v) => $v !== null),
        ];
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
        // Solo se usa el gpsTime real. Si Jimi no reporta un fix GPS nuevo,
        // fixTime queda en 0 y la posición se descarta aguas arriba: NO se inventa
        // un timestamp (eso provocaba que se guardaran posiciones con la fecha de hoy
        // aunque el dispositivo estuviera offline/sin nueva posición).
        $timeStr = $location['gpsTime'] ?? '';

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
     * @return int               Timestamp en milisegundos (UTC), o 0 si no hay fecha válida
     */
    public function parseTimestampMs(string $datetime): int
    {
        if (empty($datetime)) {
            return 0; // sin fecha → fixTime 0 → la posición se descarta (no se guarda nada)
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
            return 0; // fecha inválida → no inventar "ahora"; descartar la posición
        }
    }
}
