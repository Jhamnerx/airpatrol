<?php

namespace Tobuli\Services\Jimi;

/**
 * Servicio de Media (fotos/video) de Jimi IoT.
 *
 * Permite obtener URLs de archivos multimedia almacenados en Jimi
 * y enviar comandos de captura de foto al dispositivo.
 *
 * Métodos Jimi:
 *   - jimi.device.jimi.media.URL    → getMediaUrl()
 *   - jimi.device.jimi.take.photo   → sendCaptureCommand()
 */
class JimiMediaService
{
    protected JimiClient $client;
    protected JimiAuthService $auth;

    public function __construct(JimiClient $client, JimiAuthService $auth)
    {
        $this->client = $client;
        $this->auth   = $auth;
    }

    /**
     * Obtiene la URL de acceso a un archivo multimedia del dispositivo.
     *
     * Método: jimi.device.jimi.media.URL
     *
     * @param  string $imei     IMEI del dispositivo
     * @param  string $fileKey  Clave del archivo retornada por Jimi (ej: de un evento de foto)
     * @return array            Respuesta con la URL del archivo multimedia
     */
    public function getMediaUrl(string $imei, string $fileKey): array
    {
        return $this->client->send('jimi.device.jimi.media.URL', [
            'access_token' => $this->auth->getAccessToken(),
            'imei'         => $imei,
            'fileKey'      => $fileKey,
        ]);
    }

    /**
     * Envía un comando de captura de foto al dispositivo.
     *
     * Método: jimi.device.jimi.take.photo
     *
     * @param  string $imei     IMEI del dispositivo
     * @param  int    $channel  Canal de cámara (1 = frontal por defecto)
     * @return array            Respuesta de confirmación del comando
     */
    public function sendCaptureCommand(string $imei, int $channel = 1): array
    {
        return $this->client->send('jimi.device.jimi.take.photo', [
            'access_token' => $this->auth->getAccessToken(),
            'imei'         => $imei,
            'channel'      => $channel,
        ]);
    }
}
