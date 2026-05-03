<?php

namespace Tobuli\Services\Jimi;

use Illuminate\Support\Facades\Log;
use Tobuli\Entities\Device;

/**
 * Procesa las notificaciones push de alarma enviadas por Jimi.
 *
 * Jimi envía un POST x-www-form-urlencoded con:
 *   msgType = "jimi.push.device.alarm"
 *   data    = JSON con los campos de la alarma
 *
 * Tipos de alarmType (alertTypeId del apéndice):
 *   Numéricos  → reportados directamente por el dispositivo
 *   Texto      → generados por lógica de plataforma Jimi (ej: "geozone", "overSpeed")
 */
class JimiAlarmService
{
    /**
     * Tipos de alarma que se mapean a eventos conocidos de AirPatrol.
     *
     * Clave   = alarmType de Jimi
     * Valor   = nombre legible para logs/eventos
     */
    const ALARM_MAP = [
        // Numéricos (reportados por dispositivo)
        '1'   => 'SOS',
        '2'   => 'power_cut',
        '3'   => 'vibration',
        '4'   => 'geofence_enter',
        '5'   => 'geofence_exit',
        '6'   => 'overspeed',
        '9'   => 'displacement',
        '14'  => 'low_external_power',
        '15'  => 'low_power_protection',
        '17'  => 'power_off',
        '19'  => 'disassembly',
        '21'  => 'battery_shutdown',
        '25'  => 'internal_low_battery',
        '40'  => 'initiative_offline',
        '41'  => 'sudden_acceleration',
        '48'  => 'sudden_deceleration',
        '71'  => 'fatigue_driving',
        '82'  => 'temperature',
        '254' => 'ignition_on',
        // Texto (generados por plataforma)
        'ACC_ON'              => 'acc_on',
        'ACC_OFF'             => 'acc_off',
        'geozone'             => 'geofence',
        'overSpeed'           => 'overspeed_platform',
        'offline'             => 'offline',
        'stayAlert'           => 'parking',
        'stayAlertOn'         => 'idling',
        'mileageAlarm'        => 'maintenance',
        'drivingBehaviorAlert' => 'driving_behavior',
        'carFault'            => 'vehicle_fault',
        'high_temp_alarm'     => 'high_temperature',
        'low_temp_alarm'      => 'low_temperature',
        'DMSAlert'            => 'dms',
        'laneshift'           => 'route_deviation',
        'in'                  => 'geofence_enter',
        'out'                 => 'geofence_exit',
    ];

    /**
     * Procesa el payload completo del webhook Jimi.
     *
     * @param  string  $msgType  Tipo de mensaje (jimi.push.device.alarm | jimi.open.instruction.raw.receive)
     * @param  string  $dataJson JSON string con los datos del evento
     * @return bool
     */
    public function handle(string $msgType, string $dataJson): bool
    {
        $data = json_decode($dataJson, true);

        if (!is_array($data)) {
            Log::warning('[Jimi Push] JSON inválido en data', ['raw' => $dataJson]);
            return false;
        }

        return match ($msgType) {
            'jimi.push.device.alarm'          => $this->handleAlarm($data),
            'jimi.open.instruction.raw.receive' => $this->handleRawData($data),
            default => $this->handleUnknown($msgType, $data),
        };
    }

    /**
     * Procesa una alarma de dispositivo.
     */
    protected function handleAlarm(array $data): bool
    {
        $imei      = $data['imei']      ?? null;
        $alarmType = (string) ($data['alarmType'] ?? '');
        $alarmName = $data['alarmName'] ?? $alarmType;
        $lat       = $data['lat']       ?? null;
        $lng       = $data['lng']       ?? null;
        $alarmTime = $data['alarmTime'] ?? null;

        if (!$imei) {
            Log::warning('[Jimi Push] Alarma sin IMEI', $data);
            return false;
        }

        $mapped = self::ALARM_MAP[$alarmType] ?? 'unknown';

        Log::info('[Jimi Push] Alarma recibida', [
            'imei'      => $imei,
            'type'      => $alarmType,
            'mapped'    => $mapped,
            'name'      => $alarmName,
            'lat'       => $lat,
            'lng'       => $lng,
            'alarm_time' => $alarmTime,
        ]);

        // Buscar el dispositivo en AirPatrol por IMEI
        $device = Device::where('imei', $imei)->first();

        if (!$device) {
            Log::info('[Jimi Push] Dispositivo no encontrado en AirPatrol', ['imei' => $imei]);
            return true; // Aceptamos el push aunque no tengamos el device
        }

        // Disparar evento de AirPatrol para que los listeners existentes lo procesen
        // (alertas, notificaciones, logs, etc.)
        event(new \App\Events\JimiAlarmReceived($device, $alarmType, $alarmName, $lat, $lng, $alarmTime, $data));

        return true;
    }

    /**
     * Procesa datos raw del dispositivo (hex).
     */
    protected function handleRawData(array $data): bool
    {
        Log::info('[Jimi Push] Raw data recibido', [
            'imei'     => $data['imei']     ?? null,
            'raw_data' => $data['raw_data'] ?? null,
        ]);

        return true;
    }

    protected function handleUnknown(string $msgType, array $data): bool
    {
        Log::warning('[Jimi Push] msgType desconocido', ['msgType' => $msgType, 'data' => $data]);
        return false;
    }
}
