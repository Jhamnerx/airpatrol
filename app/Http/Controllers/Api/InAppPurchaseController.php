<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tobuli\Entities\Order;
use Tobuli\Entities\Subscription;
use Tobuli\Entities\User;
use Carbon\Carbon;

class InAppPurchaseController extends Controller
{
    /**
     * Webhook de RevenueCat - Procesa eventos de suscripción
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueCatWebhook(Request $request)
    {
        try {
            $data = $request->all();

            // Log del webhook recibido
            Log::info("RevenueCat Webhook recibido", $data);

            // Validar que contenga el evento
            if (!isset($data['event'])) {
                Log::error("RevenueCat Webhook: No se encontró el evento");
                return response()->json(['status' => 0, 'message' => 'Evento inválido'], 400);
            }

            $event = $data['event'];

            // Obtener datos importantes
            $email = $event['app_user_id'] ?? null;
            $expirationMs = $event['expiration_at_ms'] ?? null;
            $transactionId = $event['transaction_id'] ?? null;
            $productId = $event['product_id'] ?? null;
            $eventType = $event['type'] ?? null;
            $store = $event['store'] ?? 'APP_STORE';
            $environment = $event['environment'] ?? 'PRODUCTION';

            // Validar datos requeridos
            if (!$email || !$expirationMs || !$transactionId) {
                Log::error("RevenueCat Webhook: Faltan datos requeridos", [
                    'email' => $email,
                    'expiration_ms' => $expirationMs,
                    'transaction_id' => $transactionId
                ]);
                return response()->json(['status' => 0, 'message' => 'Datos incompletos'], 400);
            }

            // Buscar usuario por email (app_user_id en RevenueCat es el email)
            $user = User::where('email', $email)->first();

            if (!$user) {
                Log::warning("RevenueCat Webhook: Usuario no encontrado", ['email' => $email]);
                return response()->json(['status' => 0, 'message' => 'Usuario no encontrado'], 404);
            }

            // Convertir timestamp de milisegundos a fecha
            $expirationDate = Carbon::createFromTimestampMs($expirationMs)->format('Y-m-d H:i:s');

            // Verificar si ya existe esta transacción
            $existingSubscription = Subscription::where('gateway_id', $transactionId)->first();

            if ($existingSubscription) {
                // Si ya existe, actualizar fecha de expiración
                $existingSubscription->update([
                    'expiration_date' => $expirationDate,
                    'active' => 1
                ]);

                Log::info("RevenueCat Webhook: Suscripción actualizada", [
                    'subscription_id' => $existingSubscription->id,
                    'user_id' => $user->id,
                    'email' => $email,
                    'expiration_date' => $expirationDate
                ]);
            } else {
                // Crear nueva orden
                $order = Order::create([
                    'user_id' => $user->id,
                    'plan_id' => null,
                    'plan_type' => 'revenuecat',
                    'price' => $event['price'] ?? 0,
                    'entity_id' => $user->id,
                    'entity_type' => 'user',
                    'paid_at' => now(),
                ]);

                // Crear suscripción
                $gateway = $store === 'APP_STORE' ? 'apple_revenuecat' : 'google_revenuecat';

                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'gateway' => $gateway,
                    'gateway_id' => $transactionId,
                    'order_id' => $order->id,
                    'expiration_date' => $expirationDate,
                    'active' => 1
                ]);

                Log::info("RevenueCat Webhook: Nueva suscripción creada", [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'email' => $email,
                    'product_id' => $productId,
                    'event_type' => $eventType,
                    'environment' => $environment,
                    'expiration_date' => $expirationDate
                ]);
            }

            // Actualizar usuario
            $user->update([
                'subscription_expiration' => $expirationDate,
                'active' => 1
            ]);

            Log::info("RevenueCat Webhook: Usuario actualizado exitosamente", [
                'user_id' => $user->id,
                'email' => $email,
                'subscription_expiration' => $expirationDate,
                'event_type' => $eventType
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'Webhook procesado exitosamente'
            ]);
        } catch (\Exception $e) {
            Log::error("RevenueCat Webhook: Error procesando webhook", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Error procesando webhook: ' . $e->getMessage()
            ], 500);
        }
    }
}
