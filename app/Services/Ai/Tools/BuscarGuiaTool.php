<?php

namespace App\Services\Ai\Tools;

class BuscarGuiaTool extends AbstractTool
{
    public static function guides(): array
    {
        static $guides = null;

        return $guides ?? ($guides = require resource_path('ai/guides.php'));
    }

    public function name(): string
    {
        return 'buscar_guia';
    }

    public function description(): string
    {
        $topics = [];
        foreach (self::guides() as $key => $guide) {
            $topics[] = "$key ({$guide['titulo']})";
        }

        return 'Devuelve la guia oficial de uso de la plataforma para un tema: pasos, errores comunes y la '
            . 'pantalla relacionada. Usala ante preguntas de "como hago...". Temas: ' . implode('; ', $topics) . '.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tema' => [
                    'type' => 'string',
                    'description' => 'Clave del tema o palabras de la pregunta del usuario.',
                ],
            ],
            'required' => ['tema'],
            'additionalProperties' => false,
        ];
    }

    public function label(array $input): string
    {
        $guide = $this->find($input['tema'] ?? '');

        return 'Guia · ' . ($guide ? $guide['titulo'] : ($input['tema'] ?? ''));
    }

    protected function run(array $input): array
    {
        $guide = $this->find($input['tema']);

        if (!$guide) {
            return [
                'encontrada' => false,
                'temas_disponibles' => array_map(function ($g) {
                    return $g['titulo'];
                }, array_values(self::guides())),
            ];
        }

        return [
            'encontrada' => true,
            'titulo' => $guide['titulo'],
            'pasos' => $guide['pasos'],
            'errores_comunes' => $guide['errores'],
            'pantalla' => $guide['pantalla'],
            'video' => $guide['video'] ?? null,
        ];
    }

    private function find(string $topic): ?array
    {
        $guides = self::guides();
        $topic = mb_strtolower(trim($topic));

        if (isset($guides[$topic])) {
            return $guides[$topic];
        }

        $words = array_filter(preg_split('/[^\p{L}\p{N}]+/u', $this->plain($topic)), function ($w) {
            return mb_strlen($w) > 2;
        });

        $best = null;
        $bestScore = 0;

        foreach ($guides as $key => $guide) {
            $haystack = $this->plain($key . ' ' . $guide['titulo'] . ' ' . $guide['palabras']);
            $score = 0;

            foreach ($words as $word) {
                if (mb_strpos($haystack, $word) !== false) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $best = $guide;
                $bestScore = $score;
            }
        }

        return $best;
    }

    private function plain(string $text): string
    {
        $text = mb_strtolower($text);

        return strtr($text, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);
    }
}
