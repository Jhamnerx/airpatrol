<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conexión
    |--------------------------------------------------------------------------
    |
    | La URL, el puerto y la credencial NO se declaran aquí: ya existen.
    |   - host/puerto  → config('tracker')['web.url'] / ['web.port']
    |                    (normalizados por Tobuli\Helpers\Tracker::getUrl())
    |   - credencial   → config('app.admin_user'), que es la misma que
    |                    config('tracker')['user.password'] y la que acepta
    |                    LoginService del fork como cuenta de servicio.
    |
    | Lo único que sí hace falta declarar es la base PÚBLICA del streaming:
    | Tracker::getUrl() apunta a localhost para las llamadas servidor→Traccar,
    | pero el .m3u8 lo pide el navegador del usuario y necesita un host
    | alcanzable desde fuera. Si Traccar está detrás de un proxy en el mismo
    | dominio, deja TRACCAR_STREAM_URL con el dominio y sin puerto.
    |
    */

    'stream_url' => env('TRACCAR_STREAM_URL'),

    /*
    |--------------------------------------------------------------------------
    | Evidencias (fotos y clips)
    |--------------------------------------------------------------------------
    |
    | Traccar guarda lo que suben los equipos en <media_path>/<imei>/<archivo>
    | y lo referencia en los atributos image/video de la posición. Esto vale
    | tanto para el JC400 (comando UPLOAD) como para el JC450 (flujo automático
    | alarmLabel → VIDEOUPLOAD).
    |
    | La ruta NO se declara aquí: es config('tracker')['media.path'], la misma
    | que Tobuli\Helpers\TrackerConfig escribe en /opt/traccar/conf/traccar.xml
    | y que ya usa cameras_media_path() para el módulo de medios. Es el mismo
    | layout <ruta>/<imei>/<archivo>, así que las evidencias de los Jimi caen
    | donde tu plataforma ya sabe buscarlas.
    |
    | El navegador no puede pedir /api/media/… directamente: el MediaFilter de
    | Traccar exige sesión propia y no acepta Basic auth. Por eso airpatrol
    | sirve los archivos desde disco, detrás de sus propios permisos.
    |
    */

    /*
    | Disco de Laravel al que archivar las evidencias (por ejemplo "s3").
    | Vacío = servir directamente desde media_path.
    |
    | Con un disco configurado, media_path pasa a ser un área de paso: un job
    | copia el archivo al disco y el reproductor lo sirve desde ahí. Traccar
    | siempre escribe local primero, no sabe de S3.
    */

    'media_disk' => env('TRACCAR_MEDIA_DISK'),

    /*
    |--------------------------------------------------------------------------
    | Modelos de dispositivo
    |--------------------------------------------------------------------------
    |
    | Traccar guarda el modelo en tc_devices.model. Con
    | database.registerUnknown=true los equipos se autoregistran con model
    | NULL, así que hay que fijarlo desde aquí.
    |
    | IMPORTANTE: Jt808ProtocolDecoder compara contra un Set exacto
    | (JC371, JC181, JC182, JC450, JC451). Una grafía distinta ("jc450",
    | "JC-450", "JC450 Pro") NO da error: simplemente deja de decodificar la
    | extensión 0xE8 y se pierden atributos en silencio. Por eso el campo debe
    | ser un selector cerrado y nunca texto libre.
    |
    | JC450PRO no está en el Set de Traccar; esas unidades se registran como
    | JC450 (lo normaliza Device::traccarModel()).
    |
    */

    'models' => [

        // JT808 / JT1078. El modelo afecta al decoder (extensión 0xE8) y al
        // encoder (los comandos ASCII van en 0x8900 solo si empieza por "JC").
        'jt808' => ['JC181', 'JC182', 'JC371', 'JC450', 'JC451'],

        // GT06. La variante se autodetecta por la forma del paquete
        // (Gt06ProtocolDecoder.Variant.JC400); el modelo es informativo.
        'gt06'  => ['JC261', 'JC400', 'JC400P'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video
    |--------------------------------------------------------------------------
    */

    'video' => [
        // Cámara por defecto cuando el cliente no especifica.
        'default_channel' => 1,

        /*
        | JC261/JC400: el equipo EMPUJA RTMP a un servidor externo (MediaMTX);
        | Traccar no recibe RTMP, así que su HLS no sirve para estos modelos.
        |
        | Con RSERVICE,<host>:1935/live el equipo publica en
        | live/{canal}/{imei} (protocolo JC261-JC400 v1.3.5, sección 5.8) y
        | MediaMTX lo reexpone como HLS. Sin canal = reproducción histórica.
        |
        | La ruta exacta hay que confirmarla en los logs de MediaMTX la
        | primera vez que publique un equipo.
        */
        'rtmp' => [
            'host'      => env('TRACCAR_RTMP_HOST'),
            'app'       => env('TRACCAR_RTMP_APP', 'live'),
            'hls_url'   => env('TRACCAR_RTMP_HLS_URL'),
            'hls_file'  => env('TRACCAR_RTMP_HLS_FILE', 'index.m3u8'),
        ],

        /*
        | Captura de video bajo demanda al generarse un evento (estilo Wialon):
        | en vez de esperar a que el equipo suba el clip solo, se le pide por la
        | hora del evento.
        |
        | Solo aplica a JC261/JC400 (comando EVIDEO). El JC450 no tiene un
        | equivalente implementado: JT/T 1078 lo resuelve con 0x9206 (subida por
        | FTP de un rango horario), que no está en Traccar y necesita servidor
        | FTP. En JC450 la evidencia sigue llegando por el flujo automático de
        | alarmLabel + VIDEOUPLOAD.
        |
        | Ojo: EVIDEO recibe la DURACIÓN TOTAL del clip (10-60 s), no un margen
        | antes/después. Los segundos previos al evento se fijan una sola vez en
        | el equipo con VIDEOPARAM,<pre>,<total>.
        */
        'event_capture' => [
            'enabled'  => (bool) env('TRACCAR_EVENT_CAPTURE', false),
            'seconds'  => (int) env('TRACCAR_EVENT_CAPTURE_SECONDS', 15),
            'camera'   => (int) env('TRACCAR_EVENT_CAPTURE_CAMERA', 1),

            // Tipos de evento que disparan la petición. Vacío = todos.
            'types'    => array_filter(explode(',', (string) env('TRACCAR_EVENT_CAPTURE_TYPES', ''))),

            // No pedir dos clips del mismo equipo en esta ventana (segundos).
            'throttle' => (int) env('TRACCAR_EVENT_CAPTURE_THROTTLE', 60),
        ],

        // Polling de la lista de grabaciones: el 0x1205 llega asíncrono.
        'resource_poll_interval' => (int) env('TRACCAR_VIDEO_POLL_INTERVAL', 3),
        'resource_poll_attempts' => (int) env('TRACCAR_VIDEO_POLL_ATTEMPTS', 15),
    ],

];
