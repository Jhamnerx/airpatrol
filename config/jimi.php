<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jimi IoT / Tracksolid Open API
    |--------------------------------------------------------------------------
    | Documentación: integration_jimi.md
    |
    | URL base de la API. Cada instalación recibe una URL única de Jimi.
    | El account, password, app_key y app_secret son provistos por Jimi.
    */
    'url'        => env('JIMI_URL', 'https://us-open.tracksolidpro.com/route/rest'),

    'account'    => env('JIMI_ACCOUNT', ''),
    'password'   => env('JIMI_PASSWORD', ''),
    'app_key'    => env('JIMI_APP_KEY', ''),
    'app_secret' => env('JIMI_APP_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Token TTL
    |--------------------------------------------------------------------------
    | Tiempo de vida del access_token en segundos. Jimi permite hasta 7200 (2h).
    | Se cachea en Redis con 60 segundos de margen para evitar expiración en vuelo.
    */
    'token_ttl'  => (int) env('JIMI_TOKEN_TTL', 7000),

    /*
    |--------------------------------------------------------------------------
    | Mapeo de canal de cámara por protocolo de DVR
    |--------------------------------------------------------------------------
    | Según integration_jimi.md (secciones 4.6/4.7/4.9/4.10):
    |   - Concox (JC261, JC400, ...):       los canales empiezan en 0
    |   - JT808/1078 (JC371, JC181, JC182,
    |                 JC450, JC451, ...):   los canales empiezan en 1
    |
    | La app móvil/web envía un índice de cámara 1-based (cámara 1, 2, 3...).
    | El backend resta el offset según el modelo del equipo (jimi_model / mcType):
    |   canal_real = camara_1based - channel_base[protocolo]
    |
    | Cualquier modelo que empiece por uno de estos prefijos se considera Concox.
    | El resto se trata como JT808 (base 1), que es el caso por defecto.
    */
    'video' => [
        'concox_models' => array_map('strtoupper', (array) [
            'JC261',
            'JC400',
        ]),
    ],
];
