<?php

namespace App\Listeners;

use App\Events\JimiAlarmReceived;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Tobuli\Entities\Event;
use Tobuli\Services\EventWriteService;

class JimiAlarmListener
{
    public function handle(JimiAlarmReceived $jimiEvent): void
    {
        $device = $jimiEvent->device;

        // Preferir usuario no-admin; si no existe, usar el admin asignado al dispositivo
        $user = $device->users()->where('group_id', '!=', 1)->orderBy('id')->first()
            ?? $device->users()->orderBy('id')->first();

        if (!$user) {
            Log::warning('[Jimi] JimiAlarmListener: no hay usuario asociado al dispositivo', [
                'device_id' => $device->id,
            ]);
            return;
        }

        try {
            $now = Carbon::now();

            $event = new Event([
                'user_id'   => $user->id,
                'device_id' => $device->id,
                'type'      => Event::TYPE_CUSTOM,
                'message'   => '[Jimi] ' . $jimiEvent->alarmName,
                'latitude'  => $jimiEvent->lat,
                'longitude' => $jimiEvent->lng,
                'time'      => $jimiEvent->alarmTime ?: $now->format('Y-m-d H:i:s'),
            ]);

            $event->setCreatedAt($now);
            $event->setUpdatedAt($now);

            app(EventWriteService::class)->write([$event]);

            Log::info('[Jimi] Evento de alarma creado en historial', [
                'device_id'  => $device->id,
                'alarm_type' => $jimiEvent->alarmType,
                'alarm_name' => $jimiEvent->alarmName,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Jimi] JimiAlarmListener error: ' . $e->getMessage(), [
                'device_id' => $device->id,
            ]);
        }
    }
}
