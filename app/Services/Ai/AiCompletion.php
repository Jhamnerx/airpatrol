<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;
use Tobuli\Entities\AiUsageLog;
use Tobuli\Entities\User;

/**
 * Llamada de una sola respuesta (sin herramientas) para las funciones fuera
 * del chat: explicar reporte, resumen en correos, dashboard y resumen de
 * alertas. Registra el consumo en ai_usage_logs con su "feature".
 */
class AiCompletion
{
    /** @var ClaudeClient */
    private $client;

    public function __construct(ClaudeClient $client = null)
    {
        $this->client = $client ?? new ClaudeClient();
    }

    /**
     * @param string   $model  modelo (por defecto el del chat)
     * @param callable $onText fn(string $delta) opcional, para streaming
     * @return string texto de la respuesta
     */
    public function complete(User $user, string $feature, string $system, string $content, string $model = null,
                             string $effort = 'low', int $maxTokens = 4000, callable $onText = null): string
    {
        $model = $model ?: config('ai.model');

        $body = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => [['type' => 'text', 'text' => $system]],
            'messages' => [['role' => 'user', 'content' => [['type' => 'text', 'text' => $content]]]],
            'output_config' => ['effort' => $effort],
            'cache_control' => ['type' => 'ephemeral'],
        ];

        // El fallback de servidor solo aplica a los modelos que lo soportan.
        if (config('ai.fallbacks') && in_array($model, ['claude-sonnet-5-5', 'claude-opus-5-5'])) {
            $body['fallbacks'] = 'default';
        }

        $status = 'ok';
        $response = null;

        try {
            $response = $this->client->stream($body, $onText);

            if ($response->stop_reason === 'refusal') {
                $status = 'refusal';
                return '';
            }

            $text = '';
            foreach ($response->content as $block) {
                if (($block->type ?? null) === 'text') {
                    $text .= $block->text;
                }
            }

            return trim($text);
        } catch (\Throwable $e) {
            $status = 'error';
            throw $e;
        } finally {
            $this->log($user, $feature, $response->model ?? $model, $response->usage ?? null, $status);
        }
    }

    private function log(User $user, string $feature, string $model, $usage, string $status)
    {
        try {
            $input = (int) ($usage->input_tokens ?? 0);
            $output = (int) ($usage->output_tokens ?? 0);
            $read = (int) ($usage->cache_read_input_tokens ?? 0);
            $write = (int) ($usage->cache_creation_input_tokens ?? 0);
            $price = config("ai.pricing.$model") ?? config('ai.pricing.' . config('ai.model'));

            AiUsageLog::create([
                'user_id' => $user->id,
                'feature' => $feature,
                'model' => $model,
                'input_tokens' => $input,
                'output_tokens' => $output,
                'cache_read_tokens' => $read,
                'cache_write_tokens' => $write,
                'api_calls' => 1,
                'cost_usd' => $price ? round(($input * $price['input'] + $output * $price['output']
                    + $read * $price['cache_read'] + $write * $price['cache_write']) / 1000000, 5) : 0,
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::error('AirBot usage log error: ' . $e->getMessage());
        }
    }
}
