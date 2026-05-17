<?php

namespace Tobuli\Services\Jimi;

/**
 * Servicio de Streaming de Jimi IoT.
 *
 * Obtiene URLs para visualización de video en vivo e histórico
 * de los dispositivos con cámara.
 *
 * Métodos Jimi:
 *   - jimi.device.live.page.url           → getLiveStreamUrl()
 *   - jimi.device.media.live.stream       → getLiveStreamChannelUrl()
 *   - jimi.device.history.video.page.url  → getHistoryStreamUrl()
 */
class JimiStreamingService
{
    protected JimiClient $client;
    protected JimiAuthService $auth;

    public function __construct(JimiClient $client, JimiAuthService $auth)
    {
        $this->client = $client;
        $this->auth   = $auth;
    }

    /**
     * Obtiene la URL de la página de streaming en vivo del dispositivo.
     *
     * Método: jimi.device.live.page.url
     *
     * @param  string $imei     IMEI del dispositivo
     * @param  int    $channel  Canal de cámara (1 = frontal por defecto)
     * @return array            Respuesta con la URL de streaming en vivo
     */
    public function getLiveStreamUrl(string $imei): array
    {
        //Log::info('[JimiStreamingService] Obteniendo URL de streaming en vivo para IMEI: ' . $imei . ' Acceso token: ' . $this->auth->getAccessToken());
        return $this->client->send('jimi.device.live.page.url', [
            'access_token' => $this->auth->getAccessToken(),
            'imei'         => $imei,
            'type'         => '1',
            'voice'        => '1',
        ]);
    }

    /**
     * Obtiene la URL de stream en vivo de un canal específico.
     *
     * Método: jimi.device.media.live.stream
     *
     * @param  string $imei     IMEI del dispositivo
     * @param  int    $channel  Canal de cámara (JT808 desde 1 / Concox desde 0)
     * @param  string $appId    ID de sesión del cliente (15 caracteres)
     * @return string           URL de stream en vivo
     */
    public function getLiveStreamChannelUrl(string $imei, int $channel, string $appId): string
    {
        $result = $this->client->sendRaw('jimi.device.media.live.stream', [
            'access_token' => $this->auth->getAccessToken(),
            'imei'         => $imei,
            'channel'      => (string) $channel,
            'appId'        => $appId,
        ]);

        return is_string($result)
            ? $result
            : ($result['url'] ?? $result['UrlCamera'] ?? $result['result'] ?? '');
    }

    /**
     * Paso 1 del flujo histórico: envía el comando al dispositivo para que suba
     * su lista de archivos de video del día indicado.
     *
     * Método: jimi.device.media.history.list.cmd
     *
     * @param  string $imei           IMEI del dispositivo
     * @param  int    $channel        Canal (JT808: desde 1 / Concox: desde 0)
     * @param  string $date           Fecha en formato "Y-m-d"
     * @param  string $instructionId  UUID único para identificar la instrucción
     * @param  string $appId          15 chars alfanuméricos, único por sesión
     */
    public function sendHistoryListCmd(
        string $imei,
        int $channel,
        string $date,
        string $instructionId,
        string $appId
    ): void {
        // Log::info('[JimiStreamingService] Enviando cmd lista histórica', [
        //     'imei'          => $imei,
        //     'channel'       => $channel,
        //     'date'          => $date,
        //     'instructionId' => $instructionId,
        // ]);

        $this->client->send('jimi.device.media.history.list.cmd', [
            'access_token'  => $this->auth->getAccessToken(),
            'imei'          => $imei,
            'channel'       => $channel,
            'dateTime'      => $date,
            'instructionId' => $instructionId,
            'appId'         => $appId,
        ]);
    }

    /**
     * Paso 2 del flujo histórico: obtiene la lista de segmentos.
     *
     * Código 1207 → el dispositivo aún no subió la lista (seguir polling).
     * Recomendación: polling 1 vez/seg, máx 20 veces.
     *
     * Método: jimi.device.media.history.list.get
     *
     * @param  string $instructionId  El mismo UUID enviado en sendHistoryListCmd()
     * @return array                  Array de segmentos [{channel, beginTime, endTime, fileName, ...}]
     *
     * @throws JimiException  Con code=1207 si la lista aún no está disponible
     */
    public function getHistoryList(string $instructionId): array
    {
        return $this->client->send('jimi.device.media.history.list.get', [
            'access_token'  => $this->auth->getAccessToken(),
            'instructionId' => $instructionId,
        ]);
    }

    /**
     * Paso 3 del flujo histórico: obtiene la URL WebSocket de streaming para un segmento.
     *
     * Método: jimi.device.media.history.stream
     *
     * Para JT808/1078 (JC371, JC181, JC450, JC451): usar beginTime + endTime.
     * Para Concox (JC261, JC400): usar fileNameList.
     *
     * @param  string $imei          IMEI del dispositivo
     * @param  int    $channel       Canal de cámara
     * @param  string $appId         15 chars, mismo por sesión
     * @param  string $beginTime     UTC "Y-m-d H:i:s" (JT808)
     * @param  string $endTime       UTC "Y-m-d H:i:s" (JT808)
     * @param  string $fileNameList  Nombres de archivo separados por coma (Concox)
     * @return string                URL ws:// para flv.js
     *
     * @throws JimiException
     */
    public function getHistoryStreamUrl(
        string $imei,
        int $channel,
        string $appId,
        string $beginTime = '',
        string $endTime = '',
        string $fileNameList = ''
    ): string {
        if (!$appId) {
            $appId = $this->generateAppId();
        }

        // Log::info('[JimiStreamingService] Solicitando stream histórico', [
        //     'imei'         => $imei,
        //     'channel'      => $channel,
        //     'beginTime'    => $beginTime,
        //     'endTime'      => $endTime,
        //     'fileNameList' => $fileNameList,
        //     'appId'        => $appId,
        // ]);

        $result = $this->client->sendRaw('jimi.device.media.history.stream', [
            'access_token' => $this->auth->getAccessToken(),
            'imei'         => $imei,
            'channal'      => (int) $channel,
            'channel'      => (int) $channel,
            'beginTime'    => $beginTime,
            'endTime'      => $endTime,
            'fileNameList' => $fileNameList,
            'appId'        => $appId,
        ]);

        $url = is_string($result) ? $result : ($result['url'] ?? $result['UrlCamera'] ?? '');

        // Log::info('[JimiStreamingService] URL histórico obtenida', ['url' => $url]);

        return $url;
    }

    /**
     * Cierra un stream activo (tiempo real o histórico).
     *
     * Método: jimi.device.media.close.stream
     *
     * @param  string $imei     IMEI del dispositivo
     * @param  int    $channel  Canal de cámara
     * @param  string $appId    Mismo appId usado al abrir el stream
     * @param  string $type     '0' = tiempo real, '1' = histórico
     */
    public function closeStream(string $imei, int $channel, string $appId, string $type = '1'): void
    {
        try {
            $this->client->send('jimi.device.media.close.stream', [
                'access_token' => $this->auth->getAccessToken(),
                'imei'         => $imei,
                'channal'      => (int) $channel,
                'channel'      => (int) $channel,
                'type'         => $type,
                'appId'        => $appId,
            ]);
        } catch (\Throwable $e) {
            // Log::warning('[JimiStreamingService] Error cerrando stream', [
            //     'imei'  => $imei,
            //     'appId' => $appId,
            //     'error' => $e->getMessage(),
            // ]);
        }
    }

    /**
     * Genera un appId de 15 caracteres alfanuméricos aleatorios.
     * El mismo appId debe usarse para abrir y cerrar un stream.
     */
    public function generateAppId(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $id    = '';
        for ($i = 0; $i < 15; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $id;
    }
}
