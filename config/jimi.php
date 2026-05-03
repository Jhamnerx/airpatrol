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
];
