<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Tobuli\Entities\Device;
use Tobuli\Services\Jimi\JimiException;
use Tobuli\Services\Jimi\JimiStreamingService;

class JimiStreamController extends Controller
{
    protected JimiStreamingService $streaming;

    public function __construct(JimiStreamingService $streaming)
    {
        parent::__construct();

        $this->streaming = $streaming;
    }

    /**
     * Retorna el modal con el player de video en vivo para un dispositivo Jimi Video.
     */
    public function liveStream(int $id)
    {
        $device = $this->user->devices()->findOrFail($id);

        if (!$device->hasJimiVideo()) {
            abort(403, 'Este dispositivo no tiene capacidad de video Jimi.');
        }

        $streamUrl = null;
        $error     = null;

        try {
            $result    = $this->streaming->getLiveStreamUrl($device->imei);
            $streamUrl = $result['UrlCamera'] ?? null;
        } catch (JimiException $e) {
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('front::Jimi.live_stream', compact('device', 'streamUrl', 'error'));
    }

    /**
     * Retorna el modal con la interfaz de video histórico (sin llamar a Jimi todavía).
     * GET jimi/devices/{id}/history
     */
    public function historyStream(int $id)
    {
        $device  = $this->user->devices()->findOrFail($id);

        if (!$device->hasJimiVideo()) {
            abort(403, 'Este dispositivo no tiene capacidad de video Jimi.');
        }

        $channel = (int) request('channel', 1);
        $date    = request('date', date('Y-m-d'));

        return view('front::Jimi.history_stream', compact('device', 'channel', 'date'));
    }

    /**
     * AJAX paso 1: envía el comando al dispositivo para que suba su lista de videos.
     * POST jimi/devices/{id}/history/cmd
     * Body: channel, date (Y-m-d)
     * Returns: {instructionId, appId}
     */
    public function historyListCmd(int $id)
    {
        $device  = $this->user->devices()->findOrFail($id);
        $camera  = (int) request('channel', 1);          // índice de cámara 1-based
        $channel = $device->toJimiChannel($camera);       // canal real según protocolo
        $date    = request('date', date('Y-m-d'));

        $instructionId = (string) \Illuminate\Support\Str::uuid();
        $appId         = $this->streaming->generateAppId();

        session([
            'jimi_history_appid_'       . $device->id . '_' . $camera => $appId,
            'jimi_history_instruction_' . $device->id . '_' . $camera => $instructionId,
        ]);

        try {
            $this->streaming->sendHistoryListCmd($device->imei, $channel, $date, $instructionId, $appId);
            return response()->json(['instructionId' => $instructionId, 'appId' => $appId]);
        } catch (JimiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX paso 2 (polling): obtiene la lista de segmentos de video.
     * POST jimi/devices/{id}/history/list
     * Body: instructionId
     * Returns: {segments: [...]} | {pending: true} | {error: "..."}
     */
    public function historyList(int $id)
    {
        $this->user->devices()->findOrFail($id); // autorización
        $instructionId = request('instructionId');

        try {
            $segments = $this->streaming->getHistoryList($instructionId);
            return response()->json(['segments' => $segments ?: []]);
        } catch (JimiException $e) {
            if ($e->getCode() === 1207) {
                return response()->json(['pending' => true]);
            }
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX paso 3: obtiene la URL WebSocket de streaming para un segmento concreto.
     * POST jimi/devices/{id}/history/stream-url
     * Body: channel, appId, beginTime, endTime, fileNameList
     * Returns: {url, appId}
     */
    public function historyStreamUrl(int $id)
    {
        $device       = $this->user->devices()->findOrFail($id);
        $channel      = $device->toJimiChannel((int) request('channel', 1));
        $appId        = request('appId') ?: $this->streaming->generateAppId();
        $beginTime    = request('beginTime', '');
        $endTime      = request('endTime', '');
        $fileNameList = request('fileNameList', '');

        try {
            $url = $this->streaming->getHistoryStreamUrl(
                $device->imei,
                $channel,
                $appId,
                $beginTime,
                $endTime,
                $fileNameList
            );
            return response()->json(['url' => $url, 'appId' => $appId]);
        } catch (JimiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cierra un stream histórico activo. Llamado vía AJAX al cerrar el modal.
     * POST jimi/devices/{id}/history/close
     */
    public function closeHistoryStream(int $id)
    {
        $device  = $this->user->devices()->findOrFail($id);
        $camera  = (int) request('channel', 1);          // índice de cámara 1-based
        $channel = $device->toJimiChannel($camera);       // canal real según protocolo
        $appId   = request('appId');

        if ($appId) {
            $this->streaming->closeStream($device->imei, $channel, $appId, '1');
            session()->forget('jimi_history_appid_'       . $device->id . '_' . $camera);
            session()->forget('jimi_history_instruction_' . $device->id . '_' . $camera);
        }

        return response()->json(['success' => true]);
    }
}
