<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tobuli\Entities\Device;
use Tobuli\Services\Traccar\TraccarVideoService;

/**
 * Pide al equipo el video de un evento por su hora, sin esperar a que lo suba
 * por su cuenta (el flujo que en Wialon se hace desde la propia alerta).
 *
 * Va en cola porque PositionsWriter corre por cada posición y no puede
 * bloquearse esperando a la API de Traccar.
 */
class RequestEventVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    protected int $deviceId;
    protected string $time;
    protected ?string $eventType;

    public function __construct(int $deviceId, string $time, ?string $eventType = null)
    {
        $this->deviceId  = $deviceId;
        $this->time      = $time;
        $this->eventType = $eventType;
    }

    public function handle(TraccarVideoService $video): void
    {
        $config = config('traccar.video.event_capture');

        $device = Device::find($this->deviceId);
        if (!$device || !$device->imei) {
            return;
        }

        // Solo Concox: es el único con comando de captura por hora (EVIDEO).
        if (!$device->isJimiConcox()) {
            return;
        }

        // Un evento suele disparar varias alertas a la vez; sin esto el equipo
        // recibiría una ráfaga de EVIDEO por el mismo suceso.
        $key = "traccar.event_video.{$device->id}";
        if ($config['throttle'] > 0 && Cache::has($key)) {
            return;
        }

        try {
            $video->requestEventClip($device, $this->time, $config['camera'], $config['seconds']);

            if ($config['throttle'] > 0) {
                Cache::put($key, true, $config['throttle']);
            }

            Log::info('Video de evento solicitado', [
                'device' => $device->id,
                'time'   => $this->time,
                'type'   => $this->eventType,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo pedir el video del evento', [
                'device' => $device->id,
                'time'   => $this->time,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
