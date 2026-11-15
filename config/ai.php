<?php

/*
|--------------------------------------------------------------------------
| AirBot - asistente IA (API de Claude)
|--------------------------------------------------------------------------
| El proyecto corre en PHP 7.4/8.0, por eso no se usa el SDK oficial de
| Anthropic (requiere 8.1+). App\Services\Ai\ClaudeClient habla con la API
| por HTTP con Guzzle.
*/

return [

    // Interruptor general. Sin clave de API el asistente queda apagado.
    'enabled' => env('AI_ENABLED', true),

    'api_key'     => env('ANTHROPIC_API_KEY'),
    'base_url'    => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
    'api_version' => env('ANTHROPIC_VERSION', '2026-10-21'),

    // Modelo del chat y profundidad de razonamiento (low|medium|high|xhigh|max).
    'model'      => env('AI_MODEL', 'claude-sonnet-5-5'),
    'effort'     => env('AI_EFFORT', 'medium'),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 16000),

    // Reintento automatico en el servidor si el modelo rechaza una peticion
    // por sus filtros de seguridad (beta server-side-fallback).
    'fallbacks' => env('AI_FALLBACKS', true),

    // Timeouts de la conexion con la API (segundos).
    'connect_timeout' => 10,
    'timeout'         => (int) env('AI_TIMEOUT', 180),
    'max_retries'     => 2,

    // Limites del agente.
    'max_tool_calls'   => 6,     // llamadas a herramientas por pregunta
    'max_range_days'   => (int) env('AI_MAX_RANGE_DAYS', 31),
    'max_devices'      => (int) env('AI_MAX_DEVICES', 100),
    'history_messages' => 40,    // mensajes de la conversacion que se reenvian

    // Cuotas.
    'monthly_questions_per_user' => (int) env('AI_MONTHLY_QUESTIONS', 100), // 0 = sin limite
    'monthly_budget_usd'         => (float) env('AI_MONTHLY_BUDGET_USD', 0), // 0 = sin tope

    // Precios por millon de tokens (USD) para calcular el costo en ai_usage_logs.
    'pricing' => [
        'claude-sonnet-5-5' => ['input' => 2.00, 'output' => 10.00, 'cache_read' => 0.10, 'cache_write' => 2.50],
        'claude-haiku-5-5'  => ['input' => 0.10, 'output' => 0.50,  'cache_read' => 0.01, 'cache_write' => 0.125],
        'claude-opus-5-5'   => ['input' => 4.00, 'output' => 20.00, 'cache_read' => 0.20, 'cache_write' => 5.00],
    ],
];
