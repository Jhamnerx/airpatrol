<?php

namespace Tobuli\Services\Jimi;

use Illuminate\Support\Facades\Http;

/**
 * Cliente base para la API de Jimi IoT / Tracksolid.
 *
 * Construye los parámetros comunes, genera el SIGN y ejecuta la petición HTTP.
 *
 * SIGN = strtoupper(md5(appSecret + params_ordenados_alfabéticamente_keyvalue + appSecret))
 * - Params se ordenan por clave (ksort)
 * - Concatenación: key . value (sin separadores, sin "=")
 * - El campo "sign" NO se incluye en el cálculo
 *
 * Uso:
 *   $client = app(JimiClient::class);
 *   $result = $client->send('jimi.user.device.list', ['access_token' => $token, 'target' => $account]);
 */
class JimiClient
{
    protected string $url;
    protected string $appKey;
    protected string $appSecret;

    public function __construct()
    {
        $this->url       = (string) (config('jimi.url') ?? '');
        $this->appKey    = (string) (config('jimi.app_key') ?? '');
        $this->appSecret = (string) (config('jimi.app_secret') ?? '');
    }

    /**
     * Envía una petición a la API de Jimi.
     *
     * @param  string  $method  Nombre del método Jimi, ej: "jimi.user.device.list"
     * @param  array   $params  Parámetros privados del método (access_token, target, imei, etc.)
     * @return array            Contenido de result[] de la respuesta
     *
     * @throws JimiException  Si el código de respuesta no es 0 o hay error HTTP
     */
    public function send(string $method, array $params = []): array
    {
        $result = $this->doRequest($method, $params);

        return is_array($result) ? $result : [];
    }

    /**
     * Igual que send() pero devuelve el resultado sin filtro de tipo.
     * Útil para métodos que devuelven una URL string (ej: jimi.device.media.history.stream).
     *
     * @return array|string|null
     */
    public function sendRaw(string $method, array $params = []): mixed
    {
        return $this->doRequest($method, $params);
    }

    /**
     * Realiza la petición HTTP a Jimi y devuelve el resultado crudo (array|string|null).
     */
    private function doRequest(string $method, array $params = []): mixed
    {
        $all = array_merge([
            'method'      => $method,
            'app_key'     => $this->appKey,
            'sign_method' => 'md5',
            'timestamp'   => gmdate('Y-m-d H:i:s'), // UTC requerido por Jimi
            'format'      => 'json',
            'v'           => '1.0',
        ], $params);

        $all['sign'] = self::generateSign($all, $this->appSecret);

        try {
            $response = Http::timeout(12)->asForm()->post($this->url, $all);
            $body     = $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new JimiException('No se pudo conectar con Jimi API (timeout): ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new JimiException('Jimi HTTP request failed: ' . $e->getMessage());
        }

        if (!is_array($body) || !array_key_exists('code', $body)) {
            throw new JimiException('Respuesta inválida de Jimi API');
        }

        $code = (int) $body['code'];

        if ($code !== 0) {
            $msg = $body['message'] ?? 'Error desconocido de Jimi API';
            throw new JimiException($msg, $code, null, $body);
        }

        // Algunos endpoints devuelven 'data' en vez de 'result'
        return $body['result'] ?? $body['data'] ?? null;
    }

    /**
     * Genera el SIGN requerido por Jimi.
     *
     * Algoritmo:
     *   1. Eliminar "sign" del array de parámetros
     *   2. Ordenar claves alfabéticamente (ksort)
     *   3. Concatenar key+value (sin "=", sin separadores)
     *   4. sign = strtoupper(md5(appSecret . concatenado . appSecret))
     *
     * @param  array  $params     Todos los parámetros de la petición (excepto "sign")
     * @param  string $appSecret  App secret de Jimi
     * @return string             SIGN en mayúsculas (32 chars)
     */
    public static function generateSign(array $params, string $appSecret): string
    {
        unset($params['sign']);

        ksort($params);

        $str = '';
        foreach ($params as $key => $value) {
            // Jimi ignora params vacíos en el sign
            if ($value === '' || $value === null) {
                continue;
            }
            $str .= $key . $value;
        }

        return strtoupper(md5($appSecret . $str . $appSecret));
    }
}
