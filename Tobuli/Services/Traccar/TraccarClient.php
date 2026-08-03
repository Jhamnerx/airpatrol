<?php

namespace Tobuli\Services\Traccar;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tobuli\Helpers\Tracker;

/**
 * Cliente de la API REST de Traccar.
 *
 * Tobuli\Helpers\Tracker::api() solo hace POST y es protected, así que este
 * cliente cubre GET/PUT (necesarios para resolver el device por IMEI y para
 * fijar tc_devices.model) usando la misma credencial de servicio.
 */
class TraccarClient
{
    /** Base interna (servidor → Traccar), con la normalización de Tracker. */
    public static function baseUrl(bool $external = false): string
    {
        $url = (new Tracker)->getUrl($external);

        return rtrim($url, '/') . ':' . config('tracker')['web.port'];
    }

    protected function request(): PendingRequest
    {
        return Http::withBasicAuth('admin', (string) config('app.admin_user'))
            ->timeout(10)
            ->acceptJson()
            ->asJson()
            ->baseUrl(self::baseUrl() . '/api');
    }

    /**
     * @throws TraccarException
     */
    protected function unwrap(Response $response, string $context)
    {
        if ($response->successful()) {
            return $response->json();
        }

        Log::warning('Traccar API error', [
            'context' => $context,
            'status'  => $response->status(),
            'body'    => mb_substr((string) $response->body(), 0, 500),
        ]);

        throw new TraccarException(
            "Traccar {$context} falló (HTTP {$response->status()})",
            $response->status()
        );
    }

    /**
     * @throws TraccarException
     */
    public function get(string $path, array $query = [])
    {
        return $this->unwrap($this->request()->get($path, $query), "GET {$path}");
    }

    /**
     * @throws TraccarException
     */
    public function post(string $path, array $payload = [])
    {
        return $this->unwrap($this->request()->post($path, $payload), "POST {$path}");
    }

    /**
     * @throws TraccarException
     */
    public function put(string $path, array $payload = [])
    {
        return $this->unwrap($this->request()->put($path, $payload), "PUT {$path}");
    }

    /**
     * Devuelve el device de Traccar (tc_devices) por IMEI, o null.
     *
     * Con database.registerUnknown=true el equipo se autoregistra al primer
     * login, así que normalmente existe pero con model NULL.
     *
     * @throws TraccarException
     */
    public function findDeviceByImei(string $imei): ?array
    {
        $devices = $this->get('devices', ['uniqueId' => $imei]);

        return is_array($devices) && count($devices) ? $devices[0] : null;
    }

    /**
     * Envía un comando. El fork resuelve el device por uniqueId, así que no
     * hace falta conocer el id numérico de Traccar (CommandResource).
     *
     * @throws TraccarException
     */
    public function sendCommand(string $imei, string $type, array $attributes = [])
    {
        $payload = [
            'uniqueId' => $imei,
            'type'     => $type,
        ];

        if ($attributes) {
            $payload['attributes'] = $attributes;
        }

        return $this->post('commands/send', $payload);
    }
}
