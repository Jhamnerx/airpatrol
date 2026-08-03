<?php

namespace Tobuli\Services\Traccar;

use Carbon\Carbon;
use Tobuli\Entities\Device;

/**
 * Video de dashcams Jimi a través del fork de Traccar.
 *
 *  - JC450/JC451 (jt808): directo y reproducción por JT/T 1078, servidos como
 *    HLS por Traccar. La reproducción NO descarga nada: el equipo transmite
 *    desde su propia SD y el segmento se descarta.
 *  - JC261/JC400 (gt06): el equipo empuja RTMP a un servidor externo, así que
 *    aquí solo se envían los comandos; la URL de reproducción no sale de
 *    Traccar.
 */
class TraccarVideoService
{
    /** Acciones de 0x9202 (Jt808ProtocolEncoder TYPE_VIDEO_PLAYBACK_CONTROL). */
    const ACTION_PLAY   = 0;
    const ACTION_PAUSE  = 1;
    const ACTION_STOP   = 2;
    const ACTION_FAST   = 3;
    const ACTION_REWIND = 4;
    const ACTION_SEEK   = 5;

    protected TraccarClient $client;

    public function __construct(TraccarClient $client)
    {
        $this->client = $client;
    }

    /**
     * Traduce el índice de cámara 1-based del cliente al canal que espera el
     * equipo. Device::toJimiChannel ya distingue Concox (base 0) de JT808
     * (base 1), que es la misma numeración que usa KEY_INDEX en Traccar.
     */
    protected function channel(Device $device, ?int $camera): int
    {
        $camera = $camera ?: config('traccar.video.default_channel', 1);

        return $device->toJimiChannel((int) $camera);
    }

    /**
     * BCD YYMMDDHHMMSS. El encoder de Traccar acepta también yyyyMMddHHmmss y
     * normaliza; se envía en hora local porque es la que interpreta el equipo.
     */
    protected function time($value): string
    {
        return Carbon::parse($value)->format('ymdHis');
    }

    /**
     * El playback por señalización (0x9205/0x9201/0x9202) es JT/T 1078, o sea
     * solo JC450/JC451. En los Concox el histórico se pide con comandos ASCII
     * de subida (HVIDEO / EVIDEO / UPLOADFILE), que es otro flujo.
     *
     * @throws TraccarException
     */
    protected function assertPlaybackSupported(Device $device): void
    {
        if ($device->isJimiConcox()) {
            throw new TraccarException(
                'Este modelo no soporta reproducción remota por JT1078. '
                . 'Usa la subida de clips (HVIDEO / EVIDEO / UPLOADFILE).'
            );
        }
    }

    /** ¿Se puede listar y reproducir grabaciones desde la memoria del equipo? */
    public function supportsPlayback(Device $device): bool
    {
        return !$device->isJimiConcox();
    }

    /**
     * URL HLS servida por el fork (VideoStreamImeiResource).
     *
     * @param string $type "live" o "history"
     */
    public function streamUrl(Device $device, ?int $camera = null, string $type = 'live'): string
    {
        if ($device->isJimiConcox()) {
            return $this->rtmpStreamUrl($device, $camera, $type);
        }

        // El .m3u8 lo pide el navegador, así que hace falta un host público:
        // TRACCAR_STREAM_URL si está definido, y si no la variante externa
        // de Tracker::getUrl().
        $base = rtrim(config('traccar.stream_url') ?: TraccarClient::baseUrl(true), '/');

        return sprintf(
            '%s/api/stream/imei/%s/%d/%s.m3u8',
            $base,
            rawurlencode($device->imei),
            $this->channel($device, $camera),
            $type === 'history' ? 'history' : 'live'
        );
    }

    /**
     * URL HLS del servidor RTMP externo para JC261/JC400.
     *
     * @throws TraccarException
     */
    protected function rtmpStreamUrl(Device $device, ?int $camera, string $type): string
    {
        $rtmp = config('traccar.video.rtmp');

        if (empty($rtmp['hls_url'])) {
            throw new TraccarException(
                'Falta configurar TRACCAR_RTMP_HLS_URL: el video del JC400 lo sirve MediaMTX, no Traccar.'
            );
        }

        // Sin canal en la ruta = reproducción histórica (sección 5.8).
        $path = $type === 'history'
            ? sprintf('%s/%s', $rtmp['app'], $device->imei)
            : sprintf('%s/%d/%s', $rtmp['app'], $this->channel($device, $camera), $device->imei);

        return rtrim($rtmp['hls_url'], '/') . '/' . $path . '/' . $rtmp['hls_file'];
    }

    /**
     * @throws TraccarException
     */
    public function startLive(Device $device, ?int $camera = null): string
    {
        if ($device->isJimiConcox()) {
            // GT06 no tiene comando de video propio: se manda el ASCII por
            // 0x80. Canal 0 = frontal (OUT), 1 = cabina (IN).
            $facing = $this->channel($device, $camera) === 0 ? 'OUT' : 'IN';
            $this->sendRaw($device, 'RTMP,ON,' . $facing);
        } else {
            $this->client->sendCommand($device->imei, 'videoStart', [
                'index' => $this->channel($device, $camera),
            ]);
        }

        return $this->streamUrl($device, $camera, 'live');
    }

    /**
     * @throws TraccarException
     */
    public function stopLive(Device $device, ?int $camera = null): void
    {
        if ($device->isJimiConcox()) {
            $this->sendRaw($device, 'RTMP,OFF');
            return;
        }

        $this->client->sendCommand($device->imei, 'videoStop', [
            'index' => $this->channel($device, $camera),
        ]);
    }

    /**
     * Pide al equipo la lista de grabaciones de su SD (0x9205). La respuesta
     * (0x1205) es asíncrona: llega después como atributo videoResources de una
     * posición, que se lee con fetchResources().
     *
     * @throws TraccarException
     */
    public function queryResources(Device $device, ?int $camera, $from, $to): void
    {
        $this->assertPlaybackSupported($device);

        $this->client->sendCommand($device->imei, 'videoResourceQuery', [
            'index' => $this->channel($device, $camera),
            'from'  => $this->time($from),
            'to'    => $this->time($to),
        ]);
    }

    /**
     * Lee la última lista de grabaciones publicada por el equipo.
     *
     * @return array|null null mientras el equipo todavía no ha respondido
     * @throws TraccarException
     */
    public function fetchResources(Device $device): ?array
    {
        $traccarDevice = $this->client->findDeviceByImei($device->imei);
        if (!$traccarDevice) {
            return null;
        }

        $positions = $this->client->get('positions', ['deviceId' => $traccarDevice['id']]);
        if (!is_array($positions)) {
            return null;
        }

        foreach ($positions as $position) {
            $raw = $position['attributes']['videoResources'] ?? null;
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * Ordena reproducir un tramo (0x9201). El equipo abre el canal JT1078 y
     * Traccar lo sirve en la URL history; no se almacena nada.
     *
     * @throws TraccarException
     */
    public function startPlayback(Device $device, ?int $camera, $from, $to, int $speed = 0): string
    {
        $this->assertPlaybackSupported($device);

        $this->client->sendCommand($device->imei, 'videoPlayback', [
            'index' => $this->channel($device, $camera),
            'from'  => $this->time($from),
            'to'    => $this->time($to),
            'speed' => $speed,
        ]);

        return $this->streamUrl($device, $camera, 'history');
    }

    /**
     * Control de reproducción (0x9202): pausa, reanudar, saltar, detener.
     *
     * @throws TraccarException
     */
    public function controlPlayback(
        Device $device,
        ?int $camera,
        int $action,
        int $speed = 0,
        $position = null
    ): void {
        $this->assertPlaybackSupported($device);

        $attributes = [
            'index'  => $this->channel($device, $camera),
            'action' => $action,
            'speed'  => $speed,
        ];

        if ($action === self::ACTION_SEEK && $position) {
            $attributes['from'] = $this->time($position);
        }

        $this->client->sendCommand($device->imei, 'videoPlaybackControl', $attributes);
    }

    // -------------------------------------------------------------------------
    // Histórico de los Concox (JC261/JC400): no hay reproducción remota, se
    // piden clips que el equipo sube por HTTP al servidor de evidencias.
    // -------------------------------------------------------------------------

    /**
     * EVIDEO: genera y sube un clip alrededor de un instante.
     *
     * @param int $seconds 10-60, duración del clip (por defecto 15 según manual)
     * @throws TraccarException
     */
    public function requestEventClip(Device $device, $at, ?int $camera = null, int $seconds = 15)
    {
        $facing = $this->channel($device, $camera) === 0 ? 1 : 2; // 1=frontal 2=cabina
        $seconds = max(10, min(60, $seconds));

        return $this->sendRaw($device, sprintf(
            'EVIDEO,%s,%d,%d',
            Carbon::parse($at)->format('Y-m-d H:i:s'),
            $facing,
            $seconds
        ));
    }

    /**
     * HVIDEO: sube un minuto de sub-stream a partir de un instante.
     *
     * @throws TraccarException
     */
    public function requestMinuteClip(Device $device, $at, ?int $camera = null)
    {
        $facing = $this->channel($device, $camera) === 0 ? 1 : 2;

        return $this->sendRaw($device, sprintf(
            'HVIDEO,%s,%d',
            Carbon::parse($at)->format('Y_m_d_H_i_s'),
            $facing
        ));
    }

    /**
     * UPLOADFILE: sube un archivo puntual por nombre (el que reporta FILELIST).
     *
     * @throws TraccarException
     */
    public function requestFile(Device $device, string $filename)
    {
        return $this->sendRaw($device, 'UPLOADFILE,' . $filename);
    }

    // -------------------------------------------------------------------------
    // Evidencias / alertas con media
    // -------------------------------------------------------------------------

    /**
     * Fotos y clips que el equipo subió al servidor de evidencias.
     *
     * Traccar los guarda en media.path/<imei>/<archivo> y los referencia en los
     * atributos image/video de la posición.
     *
     * @throws TraccarException
     */
    public function listMedia(Device $device, $from = null, $to = null): array
    {
        $traccarDevice = $this->client->findDeviceByImei($device->imei);
        if (!$traccarDevice) {
            return [];
        }

        $positions = $this->client->get('positions', [
            'deviceId' => $traccarDevice['id'],
            'from'     => Carbon::parse($from ?: '-7 days')->toIso8601ZuluString(),
            'to'       => Carbon::parse($to ?: 'now')->toIso8601ZuluString(),
        ]);

        $media = [];

        foreach ((array) $positions as $position) {
            $attributes = $position['attributes'] ?? [];

            foreach (['image' => 'image', 'video' => 'video'] as $key => $type) {
                if (empty($attributes[$key])) {
                    continue;
                }

                $media[] = [
                    'type'     => $type,
                    'file'     => $attributes[$key],
                    'time'     => $position['deviceTime'] ?? $position['fixTime'] ?? null,
                    'alarm'    => $attributes['alarm'] ?? ($attributes['alarmLabel'] ?? null),
                    'event'    => $attributes['eventType'] ?? null,
                    'latitude'  => $position['latitude'] ?? null,
                    'longitude' => $position['longitude'] ?? null,
                ];
            }
        }

        return $media;
    }

    /**
     * Valida el nombre del archivo. Viene de un atributo que pone el equipo,
     * así que no puede llevar barras ni "..".
     *
     * @throws TraccarException
     */
    protected function safeName(string $filename): string
    {
        $safe = basename(str_replace('\\', '/', $filename));

        if ($safe === '' || $safe !== $filename) {
            throw new TraccarException('Nombre de archivo inválido.');
        }

        return $safe;
    }

    /** Ruta relativa común a disco local y disco de Laravel. */
    public function mediaKey(Device $device, string $filename): string
    {
        return $device->imei . '/' . $this->safeName($filename);
    }

    /**
     * Ruta local del archivo tal y como lo escribió Traccar.
     *
     * @throws TraccarException
     */
    public function mediaPath(Device $device, string $filename): string
    {
        // Mismo helper que usa el módulo de medios: la ruta sale de
        // config('tracker')['media.path'], que es lo que TrackerConfig escribe
        // como media.path en el traccar.xml real.
        $path = cameras_media_path($this->mediaKey($device, $filename));

        if (!is_file($path)) {
            throw new TraccarException('El archivo no está disponible en el servidor.');
        }

        return $path;
    }

    /**
     * Cómo servir una evidencia: desde el disco de Laravel si está archivada,
     * y si no desde el área local donde escribe Traccar.
     *
     * @return array{disk: ?string, path: string}
     * @throws TraccarException
     */
    public function mediaLocation(Device $device, string $filename): array
    {
        $disk = config('traccar.media_disk');
        $key  = $this->mediaKey($device, $filename);

        if ($disk && \Illuminate\Support\Facades\Storage::disk($disk)->exists($key)) {
            return ['disk' => $disk, 'path' => $key];
        }

        return ['disk' => null, 'path' => $this->mediaPath($device, $filename)];
    }

    /**
     * Comando ASCII crudo. Para JC450 requiere que tc_devices.model empiece
     * por "JC": si no, Jt808ProtocolEncoder intenta interpretar el texto como
     * hexadecimal y el comando falla.
     *
     * @throws TraccarException
     */
    public function sendRaw(Device $device, string $command)
    {
        return $this->client->sendCommand($device->imei, 'custom', ['data' => $command]);
    }

    /**
     * Escribe tc_devices.model vía API para que Traccar invalide su cache.
     *
     * Nota: el decoder toma el modelo del DeviceSession, que se crea al
     * conectar, así que el cambio no aplica hasta que el equipo reconecta.
     * El encoder sí lo lee del cache y aplica antes.
     *
     * @throws TraccarException
     */
    public function syncModel(Device $device, string $model): bool
    {
        $traccarDevice = $this->client->findDeviceByImei($device->imei);
        if (!$traccarDevice) {
            throw new TraccarException("El IMEI {$device->imei} no existe en Traccar todavía.");
        }

        if (($traccarDevice['model'] ?? null) === $model) {
            return false;
        }

        $traccarDevice['model'] = $model;
        $this->client->put('devices/' . $traccarDevice['id'], $traccarDevice);

        return true;
    }
}
