<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tobuli\Services\Jimi\JimiAlarmService;

/**
 * Recibe notificaciones push de Jimi IoT.
 *
 * Jimi envía POST x-www-form-urlencoded a esta URL con:
 *   msgType = tipo de mensaje (jimi.push.device.alarm, etc.)
 *   data    = JSON string con el contenido del evento
 *
 * La URL de este endpoint debe proporcionarse manualmente a soporte de Jimi
 * para que la configuren en el backend.
 *
 * Ruta: POST /jimi/webhook
 */
class JimiWebhookController extends Controller
{
    public function __construct()
    {
        // Sin middleware de auth — Jimi llama sin token de sesión
    }

    public function receive(Request $request, JimiAlarmService $alarmService): Response
    {
        $msgType = $request->input('msgType', '');
        $data    = $request->input('data', '');

        Log::debug('[Jimi Webhook] Recibido', [
            'ip'      => $request->ip(),
            'msgType' => $msgType,
            'data'    => $data,
        ]);

        if (empty($msgType) || empty($data)) {
            Log::warning('[Jimi Webhook] Payload incompleto', $request->all());
            // Jimi espera HTTP 200 aunque haya error — si devolvemos otro código puede reintentar
            return response('missing parameters', 200);
        }

        $alarmService->handle($msgType, $data);

        return response('ok', 200);
    }
}
