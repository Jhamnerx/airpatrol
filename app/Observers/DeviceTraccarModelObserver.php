<?php

namespace App\Observers;

use Illuminate\Support\Facades\Log;
use Tobuli\Entities\Device;
use Tobuli\Services\Traccar\TraccarVideoService;

/**
 * Propaga el modelo de cámara a tc_devices.model cuando cambia en GPSWOX.
 *
 * Traccar autoregistra los equipos (database.registerUnknown=true) pero los
 * crea con model NULL, y ese campo decide si el decoder JT808 interpreta la
 * extensión 0xE8 y si los comandos ASCII se encapsulan bien. Sin esto habría
 * que editarlo a mano en la base de Traccar.
 */
class DeviceTraccarModelObserver
{
    protected TraccarVideoService $video;

    public function __construct(TraccarVideoService $video)
    {
        $this->video = $video;
    }

    public function saved(Device $device): void
    {
        if (!$device->wasChanged('jimi_model')) {
            return;
        }

        $model = $device->traccarModel();

        if (!$model || !$device->imei) {
            return;
        }

        // Nunca romper el guardado del device por un fallo de Traccar: el
        // modelo se puede reintentar, perder el formulario no.
        try {
            $this->video->syncModel($device, $model);
        } catch (\Throwable $e) {
            Log::warning('No se pudo sincronizar el modelo con Traccar', [
                'device' => $device->id,
                'imei'   => $device->imei,
                'model'  => $model,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
