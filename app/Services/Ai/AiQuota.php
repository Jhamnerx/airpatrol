<?php

namespace App\Services\Ai;

use Tobuli\Entities\AiUsageLog;
use Tobuli\Entities\User;

/**
 * Cuotas del asistente: preguntas por usuario al mes y tope global en USD.
 */
class AiQuota
{
    public static function limit(): int
    {
        return (int) config('ai.monthly_questions_per_user', 0);
    }

    public static function used(User $user): int
    {
        return AiUsageLog::where('user_id', $user->id)
            ->thisMonth()
            ->where('status', '!=', 'error')
            ->count();
    }

    /** null = sin limite */
    public static function remaining(User $user): ?int
    {
        $limit = self::limit();

        return $limit > 0 ? max(0, $limit - self::used($user)) : null;
    }

    public static function budgetExceeded(): bool
    {
        $budget = (float) config('ai.monthly_budget_usd', 0);

        if ($budget <= 0) {
            return false;
        }

        return (float) AiUsageLog::thisMonth()->sum('cost_usd') >= $budget;
    }
}
