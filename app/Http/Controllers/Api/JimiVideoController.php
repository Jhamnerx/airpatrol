<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tobuli\Services\Jimi\JimiException;
use Tobuli\Services\Jimi\JimiStreamingService;

/**
 * API de Video Jimi para React Native.
 *
 * Todos los endpoints requieren Bearer token (Passport).
 * El dispositivo debe pertenecer al usuario autenticado.
 *
 * Flujo video en vivo:
 *   GET  api/jimi/devices/{id}/live           → URL para el player
 *
 * Flujo video histórico (3 pasos):
 *   POST api/jimi/devices/{id}/history/cmd    → Envía cmd al dispositivo
 *   POST api/jimi/devices/{id}/history/list   → Polling segmentos (repetir si pending=true)
 *   POST api/jimi/devices/{id}/history/stream → URL WS del segmento seleccionado
 *   POST api/jimi/devices/{id}/history/close  → Cierra el stream
 */
class JimiVideoController extends Controller
{
    protected JimiStreamingService $streaming;

    public function __construct(JimiStreamingService $streaming)
    {
        parent::__construct();
        $this->streaming = $streaming;
    }

    /* ──────────────────────────────────────────────
     |  VIDEO EN VIVO
     |  GET api/jimi/devices/{id}/live
     |
     |  Response 200:
     |  {
     |    "url": "https://...",
     |    "imei": "865478070000239",
     |    "device_id": 42,
     |    "device_name": "Unidad 01"
     |  }
     └─────────────────────────────────────────────*/
    public function liveStream(int $id)
    {
        $device = $this->user->devices()->find($id);

        if (!$device) {
            return response()->json(['error' => 'Dispositivo no encontrado.'], 404);
        }

        if (!$device->hasJimiVideo()) {
            return response()->json(['error' => 'Este dispositivo no tiene capacidad de video Jimi.'], 422);
        }

        try {
            $result = $this->streaming->getLiveStreamUrl($device->imei);
            $url    = $result['UrlCamera'] ?? $result['url'] ?? null;

            if (!$url) {
                return response()->json(['error' => 'No se pudo obtener la URL de streaming.'], 422);
            }

            return response()->json([
                'url'         => $url,
                'imei'        => $device->imei,
                'device_id'   => $device->id,
                'device_name' => $device->name,
            ]);
        } catch (JimiException $e) {
            Log::warning('[JimiVideoApi] live error', ['device' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('[JimiVideoApi] live exception', ['device' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }

    /* ──────────────────────────────────────────────
     |  VIDEO EN VIVO POR CANAL
     |  POST api/jimi/devices/{id}/live/stream
     |
     |  Body: { "channel": 1, "app_id": "aB3xK9pLmQr7t" }
     |
     |  Response 200:
     |  {
     |    "url": "ws://...live.flv?secret=...",
     |    "app_id": "aB3xK9pLmQr7t"
     |  }
     └─────────────────────────────────────────────*/
    public function liveStreamChannel(int $id)
    {
        $device = $this->user->devices()->find($id);

        if (!$device) {
            return response()->json(['error' => 'Dispositivo no encontrado.'], 404);
        }

        if (!$device->hasJimiVideo()) {
            return response()->json(['error' => 'Este dispositivo no tiene capacidad de video Jimi.'], 422);
        }

        if (!request()->has('channel') || request('channel') === '') {
            return response()->json(['error' => 'El campo "channel" es requerido.'], 422);
        }

        // La app envía un índice de cámara 1-based; lo traducimos al canal real
        // según el protocolo del equipo (Concox base 0 / JT808 base 1).
        $channel = $device->toJimiChannel((int) request('channel'));
        $appId   = (string) request('app_id', '');

        if ($appId === '') {
            $appId = $this->streaming->generateAppId();
        }

        try {
            $url = $this->streaming->getLiveStreamChannelUrl($device->imei, $channel, $appId);

            if (!$url) {
                return response()->json(['error' => 'No se pudo obtener la URL de streaming.'], 422);
            }

            return response()->json([
                'url'    => $url,
                'app_id' => $appId,
            ]);
        } catch (JimiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }

    /* ──────────────────────────────────────────────
     |  HISTÓRICO - PASO 1: ENVIAR COMANDO
     |  POST api/jimi/devices/{id}/history/cmd
     |
     |  Body: { "channel": 1, "date": "2026-04-27" }
     |
     |  Response 200:
     |  {
     |    "instruction_id": "uuid",
     |    "app_id": "aB3xK9pLmQr7t",
     |    "poll_interval_ms": 1000,
     |    "max_polls": 20
     |  }
     └─────────────────────────────────────────────*/
    public function historyCmd(int $id)
    {
        $device = $this->user->devices()->find($id);

        if (!$device) {
            return response()->json(['error' => 'Dispositivo no encontrado.'], 404);
        }

        if (!$device->hasJimiVideo()) {
            return response()->json(['error' => 'Este dispositivo no tiene capacidad de video Jimi.'], 422);
        }

        if ($device->isOffline()) {
            return response()->json([
                'error'      => 'El dispositivo est\u00e1 offline. Debe estar en l\u00ednea para solicitar video hist\u00f3rico.',
                'error_code' => 'DEVICE_OFFLINE',
                'retryable'  => true,
            ], 422);
        }

        $camera = (int) request('channel', 1);
        $date   = request('date');

        if (empty($device->imei)) {
            return response()->json(['error' => 'El dispositivo no tiene IMEI configurado.'], 422);
        }

        if ($camera < 1) {
            return response()->json(['error' => 'El campo "channel" (índice de cámara) debe ser un entero mayor o igual a 1.'], 422);
        }

        // Traducción a canal real según protocolo del equipo (Concox base 0 / JT808 base 1).
        $channel = $device->toJimiChannel($camera);

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json(['error' => 'El campo "date" es requerido en formato Y-m-d.'], 422);
        }

        $instructionId = (string) Str::uuid();
        $appId         = $this->streaming->generateAppId();

        try {
            $this->streaming->sendHistoryListCmd($device->imei, $channel, $date, $instructionId, $appId);

            return response()->json([
                'instruction_id'  => $instructionId,
                'app_id'          => $appId,
                'poll_interval_ms' => 1000,
                'max_polls'       => 20,
            ]);
        } catch (JimiException $e) {
            $payload = $this->buildJimiErrorPayload($e);

            Log::warning('[JimiVideoApi] history cmd jimi error', [
                'device_id'    => $id,
                'imei'         => $device->imei,
                'channel'      => $channel,
                'date'         => $date,
                'jimi_code'    => $e->getCode(),
                'jimi_message' => $e->getMessage(),
                'error_code'   => $payload['error_code'] ?? null,
                'raw'          => $e->rawResponse,
            ]);

            return response()->json($payload, 422);
        } catch (\Throwable $e) {
            Log::error('[JimiVideoApi] history cmd exception', ['device' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }

    /**
     * Normaliza errores crudos de Jimi para respuestas de API estables y accionables.
     */
    private function buildJimiErrorPayload(JimiException $e): array
    {
        $message = trim((string) $e->getMessage());

        if (stripos($message, 'device is not registered in the routing table') !== false) {
            return [
                'error'      => 'No se pudo enviar el comando al dispositivo. El equipo no est\u00e1 registrado en la tabla de enrutamiento de Jimi. Reinicia el dispositivo y vuelve a intentar en 1-2 minutos.',
                'error_code' => 'JIMI_DEVICE_NOT_IN_ROUTING_TABLE',
                'retryable'  => true,
                'jimi_code'  => $e->getCode(),
            ];
        }

        return [
            'error'      => $message !== '' ? $message : 'Error de Jimi API.',
            'error_code' => 'JIMI_API_ERROR',
            'retryable'  => false,
            'jimi_code'  => $e->getCode(),
        ];
    }

    /* ──────────────────────────────────────────────
     |  HISTÓRICO - PASO 2: OBTENER LISTA (POLLING)
     |  POST api/jimi/devices/{id}/history/list
     |
     |  Body: { "instruction_id": "uuid" }
     |
     |  Response 200 (lista disponible):
     |  {
     |    "pending": false,
     |    "segments": [
     |      {
     |        "channel": "1",
     |        "begin_time": "2026-04-27 10:00:00",
     |        "end_time":   "2026-04-27 10:03:00",
     |        "file_name":  null,
     |        "file_size":  8325778
     |      }, ...
     |    ]
     |  }
     |
     |  Response 200 (aún no disponible → repetir polling):
     |  { "pending": true }
     └─────────────────────────────────────────────*/
    public function historyList(int $id)
    {
        // Solo verificamos que el dispositivo pertenece al usuario
        if (!$this->user->devices()->find($id)) {
            return response()->json(['error' => 'Dispositivo no encontrado.'], 404);
        }

        $instructionId = request('instruction_id');

        if (!$instructionId) {
            return response()->json(['error' => 'El campo "instruction_id" es requerido.'], 422);
        }

        try {
            $raw = $this->streaming->getHistoryList($instructionId);

            $segments = array_map(function ($seg) {
                return [
                    'channel'    => $seg['channel']    ?? null,
                    'begin_time' => $seg['beginTime']  ?? null,
                    'end_time'   => $seg['endTime']    ?? null,
                    'file_name'  => $seg['fileName']   ?? null,
                    'file_size'  => $seg['fileSize']   ?? null,
                    'sort_begin' => $seg['sortBeginTime'] ?? null,
                ];
            }, $raw ?: []);

            return response()->json(['pending' => false, 'segments' => $segments]);
        } catch (JimiException $e) {
            if ($e->getCode() === 1207) {
                return response()->json(['pending' => true]);
            }
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }

    /* ──────────────────────────────────────────────
     |  HISTÓRICO - PASO 3: OBTENER URL DE STREAM
     |  POST api/jimi/devices/{id}/history/stream
     |
     |  Body (JT808/1078 - JC450, JC451...):
     |  {
     |    "channel":    1,
     |    "app_id":     "aB3xK9pLmQr7t",
     |    "begin_time": "2026-04-27 10:00:00",
     |    "end_time":   "2026-04-27 10:03:00"
     |  }
     |
     |  Body (Concox - JC261, JC400...):
     |  {
     |    "channel":        0,
     |    "app_id":         "aB3xK9pLmQr7t",
     |    "file_name_list": "2026_04_27_10_00_00_01.mp4"
     |  }
     |
     |  Response 200:
     |  {
     |    "url":    "ws://113.108.62.203:11014/1/865478070000239.history.flv?secret=...",
     |    "app_id": "aB3xK9pLmQr7t"
     |  }
     └─────────────────────────────────────────────*/
    public function historyStreamUrl(int $id)
    {
        $device = $this->user->devices()->find($id);

        if (!$device) {
            return response()->json(['error' => 'Dispositivo no encontrado.'], 404);
        }

        // channel = índice de cámara 1-based (el mismo usado en history/cmd).
        $channel      = $device->toJimiChannel((int) request('channel', 1));
        $appId        = request('app_id', '');
        $beginTime    = request('begin_time', '');
        $endTime      = request('end_time', '');
        $fileNameList = request('file_name_list', '');

        if (!$appId) {
            $appId = $this->streaming->generateAppId();
        }

        try {
            $url = $this->streaming->getHistoryStreamUrl(
                $device->imei,
                $channel,
                $appId,
                $beginTime,
                $endTime,
                $fileNameList
            );

            if (!$url) {
                return response()->json(['error' => 'No se obtuvo URL de streaming.'], 422);
            }

            return response()->json(['url' => $url, 'app_id' => $appId]);
        } catch (JimiException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error interno del servidor.'], 500);
        }
    }

    /* ──────────────────────────────────────────────
     |  CERRAR STREAM (EN VIVO O HISTÓRICO)
     |  POST api/jimi/devices/{id}/history/close
     |
     |  Body: { "channel": 1, "app_id": "aB3xK9pLmQr7t", "type": "1" }
     |  type: "0" = en vivo, "1" = histórico (default)
     |
     |  Response 200: { "success": true }
     └─────────────────────────────────────────────*/
    public function closeStream(int $id)
    {
        $device = $this->user->devices()->find($id);

        if (!$device) {
            return response()->json(['error' => 'Dispositivo no encontrado.'], 404);
        }

        // channel = índice de cámara 1-based (el mismo usado al abrir el stream).
        $channel = $device->toJimiChannel((int) request('channel', 1));
        $appId   = request('app_id', '');
        $type    = request('type', '1');

        if ($appId) {
            $this->streaming->closeStream($device->imei, $channel, $appId, $type);
        }

        return response()->json(['success' => true]);
    }
}
