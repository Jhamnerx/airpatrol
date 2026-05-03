<?php

namespace App\Events;

use Tobuli\Entities\Device;

/**
 * Evento disparado cuando Jimi envía una alarma push.
 *
 * Los listeners existentes de AirPatrol pueden suscribirse a este evento
 * para procesar la alarma (notificaciones, logs, etc.)
 */
class JimiAlarmReceived
{
    public Device $device;
    public string $alarmType;
    public string $alarmName;
    public ?string $lat;
    public ?string $lng;
    public ?string $alarmTime;
    public array $raw;

    public function __construct(
        Device $device,
        string $alarmType,
        string $alarmName,
        ?string $lat,
        ?string $lng,
        ?string $alarmTime,
        array $raw = []
    ) {
        $this->device    = $device;
        $this->alarmType = $alarmType;
        $this->alarmName = $alarmName;
        $this->lat       = $lat;
        $this->lng       = $lng;
        $this->alarmTime = $alarmTime;
        $this->raw       = $raw;
    }
}
