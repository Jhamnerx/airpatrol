<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Tobuli\Services\Traccar\TraccarException;
use Tobuli\Services\Traccar\TraccarVideoService;

/**
 * Video servido por el fork de Traccar, en paralelo a JimiStreamController
 * (Tracksolid). Se elige uno u otro por dispositivo, así se puede migrar
 * equipo por equipo sin cortar el servicio.
 */
class TraccarStreamController extends Controller
{
    protected TraccarVideoService $video;

    public function __construct(TraccarVideoService $video)
    {
        parent::__construct();

        $this->video = $video;
    }

    protected function device(int $id)
    {
        return $this->user->devices()->findOrFail($id);
    }

    protected function fail(\Throwable $e, int $status = 422)
    {
        return response()->json(['error' => $e->getMessage()], $e instanceof TraccarException ? $status : 500);
    }

    /**
     * Modal con el player de directo.
     * GET traccar/devices/{id}/live
     */
    public function liveStream(int $id)
    {
        $device = $this->device($id);
        $camera = (int) request('channel', 1);

        $streamUrl = null;
        $error     = null;

        try {
            $streamUrl = $this->video->startLive($device, $camera);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('front::Traccar.live_stream', compact('device', 'camera', 'streamUrl', 'error'));
    }

    /**
     * Arranca el directo en un canal y devuelve la URL HLS.
     * POST traccar/devices/{id}/live/start
     */
    public function startLive(int $id)
    {
        $device = $this->device($id);

        try {
            return response()->json([
                'url' => $this->video->startLive($device, (int) request('channel', 1)),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * POST traccar/devices/{id}/live/stop
     */
    public function stopLive(int $id)
    {
        $device = $this->device($id);

        try {
            $this->video->stopLive($device, (int) request('channel', 1));
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * Modal de reproducción (sin pedir nada al equipo todavía).
     * GET traccar/devices/{id}/history
     */
    public function historyStream(int $id)
    {
        $device = $this->device($id);
        $camera = (int) request('channel', 1);
        $date   = request('date', date('Y-m-d'));

        // JC450: lista la SD y reproduce en streaming.
        // JC261/JC400: no hay reproducción remota, se piden clips que el equipo
        // sube al servidor de evidencias.
        $playback = $this->video->supportsPlayback($device);

        return view('front::Traccar.history_stream', compact('device', 'camera', 'date', 'playback'));
    }

    /**
     * Concox: pedir un clip al equipo (EVIDEO / HVIDEO / UPLOADFILE).
     * POST traccar/devices/{id}/history/clip
     */
    public function requestClip(int $id)
    {
        $device = $this->device($id);
        $camera = (int) request('channel', 1);

        try {
            $file = request('file');

            if ($file) {
                $this->video->requestFile($device, $file);
            } elseif (request('mode') === 'minute') {
                $this->video->requestMinuteClip($device, request('at'), $camera);
            } else {
                $this->video->requestEventClip($device, request('at'), $camera, (int) request('seconds', 15));
            }

            return response()->json(['queued' => true]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * Fotos y clips subidos por el equipo (evidencias de alarma incluidas).
     * POST traccar/devices/{id}/media
     */
    public function media(int $id)
    {
        $device = $this->device($id);

        try {
            return response()->json([
                'media' => $this->video->listMedia($device, request('from'), request('to')),
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * Sirve un archivo de evidencia. Traccar no puede servirlo al navegador
     * (su MediaFilter exige sesión propia), así que se lee del disco compartido.
     * GET traccar/devices/{id}/media/{filename}
     */
    public function mediaFile(int $id, string $filename)
    {
        $device = $this->device($id);

        try {
            $location = $this->video->mediaLocation($device, $filename);

            if ($location['disk']) {
                return \Illuminate\Support\Facades\Storage::disk($location['disk'])
                    ->response($location['path']);
            }

            return response()->file($location['path']);
        } catch (\Throwable $e) {
            abort(404);
        }
    }

    /**
     * Paso 1: pedir al equipo que liste sus grabaciones (0x9205).
     * POST traccar/devices/{id}/history/query
     */
    public function queryResources(int $id)
    {
        $device = $this->device($id);
        $date   = request('date', date('Y-m-d'));

        try {
            $this->video->queryResources(
                $device,
                (int) request('channel', 1),
                request('from', $date . ' 00:00:00'),
                request('to', $date . ' 23:59:59')
            );

            return response()->json(['queued' => true]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * Paso 2 (polling): leer la lista que subió el equipo (0x1205).
     * POST traccar/devices/{id}/history/list
     */
    public function listResources(int $id)
    {
        $device = $this->device($id);

        try {
            $resources = $this->video->fetchResources($device);

            if ($resources === null) {
                return response()->json(['pending' => true]);
            }

            return response()->json(['resources' => $resources]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * Paso 3: reproducir un tramo (0x9201) y devolver la URL HLS.
     * POST traccar/devices/{id}/history/play
     */
    public function playback(int $id)
    {
        $device = $this->device($id);

        try {
            $url = $this->video->startPlayback(
                $device,
                (int) request('channel', 1),
                request('from'),
                request('to'),
                (int) request('speed', 0)
            );

            return response()->json(['url' => $url]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /**
     * Pausa / reanudar / saltar / detener (0x9202).
     * POST traccar/devices/{id}/history/control
     */
    public function playbackControl(int $id)
    {
        $device = $this->device($id);

        try {
            $this->video->controlPlayback(
                $device,
                (int) request('channel', 1),
                (int) request('action', TraccarVideoService::ACTION_PAUSE),
                (int) request('speed', 0),
                request('position')
            );

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
