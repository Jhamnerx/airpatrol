<?php

namespace Tobuli\Helpers\Dashboard\Blocks;

use App\Services\Ai\AiFeatures;

/**
 * "Resumen AirBot": estado actual y actividad de ayer resumidos por la IA.
 * El texto se pide por AJAX (ai.dashboard_summary) para no frenar el dashboard.
 */
class AiSummaryBlock extends Block
{
    protected function getName()
    {
        return 'ai_summary';
    }

    protected function getContent()
    {
        return ['url' => route('ai.dashboard_summary')];
    }

    protected function isEnabled(): bool
    {
        return AiFeatures::enabled($this->user, 'dashboard');
    }
}
