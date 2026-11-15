<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiQuota;
use App\Services\Ai\ClaudeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tobuli\Entities\AiActionLog;
use Tobuli\Entities\AiUsageLog;

/**
 * Admin > Registros > AirBot (IA): consumo del mes, costo diario, acciones
 * confirmadas y cuotas (por defecto, por plan y tope mensual en USD).
 */
class AiAssistantController extends Controller
{
    const FEATURES = [
        'chat' => 'Chat',
        'explain_report' => 'Explicar reporte',
        'report_email' => 'Resumen en correos',
        'dashboard' => 'Resumen del dashboard',
        'alerts_digest' => 'Resumen diario de alertas',
    ];

    public function index()
    {
        $monthStart = date('Y-m-01 00:00:00');
        $month = AiUsageLog::where('ai_usage_logs.created_at', '>=', $monthStart);

        $byFeature = (clone $month)
            ->selectRaw('feature, COUNT(*) as calls, SUM(cost_usd) as cost')
            ->groupBy('feature')->get()->keyBy('feature');

        $byUser = (clone $month)
            ->leftJoin('users', 'users.id', '=', 'ai_usage_logs.user_id')
            ->selectRaw("users.email, SUM(CASE WHEN feature IN ('chat','explain_report') THEN 1 ELSE 0 END) as questions, SUM(cost_usd) as cost")
            ->groupBy('users.email')->orderByDesc('cost')->limit(20)->get();

        $daily = AiUsageLog::where('created_at', '>=', date('Y-m-d 00:00:00', strtotime('-29 days')))
            ->selectRaw('DATE(created_at) as day, SUM(cost_usd) as cost, COUNT(*) as calls')
            ->groupBy('day')->orderBy('day')->get();

        $actions = AiActionLog::with('user:id,email')->orderByDesc('id')->limit(50)->get();

        $plans = DB::table('billing_plans')->orderBy('title')->get(['id', 'title']);

        $usersWithAccess = DB::table('user_permissions')->where('name', 'ai_assistant')->where('view', 1)->count();
        $plansWithAccess = DB::table('billing_plan_permissions')->where('name', 'ai_assistant')->where('view', 1)->pluck('plan_id')->all();

        return view('admin::AiAssistant.index', [
            'configured' => ClaudeClient::isConfigured(),
            'model' => config('ai.model'),
            'features' => self::FEATURES,
            'byFeature' => $byFeature,
            'totalCost' => (float) (clone $month)->sum('cost_usd'),
            'totalErrors' => (clone $month)->where('status', 'error')->count(),
            'byUser' => $byUser,
            'daily' => $daily,
            'actions' => $actions,
            'plans' => $plans,
            'plansWithAccess' => $plansWithAccess,
            'usersWithAccess' => $usersWithAccess,
            'settings' => AiQuota::settings(),
            'defaults' => [
                'quota' => (int) config('ai.monthly_questions_per_user'),
                'budget' => (float) config('ai.monthly_budget_usd'),
            ],
        ]);
    }

    public function save(Request $request)
    {
        $this->validate($request, [
            'default_quota' => 'nullable|integer|min:0|max:100000',
            'monthly_budget_usd' => 'nullable|numeric|min:0|max:1000000',
            'plan_quotas' => 'nullable|array',
            'plan_quotas.*' => 'nullable|integer|min:0|max:100000',
        ]);

        $planQuotas = [];
        foreach ((array) $request->input('plan_quotas', []) as $planId => $value) {
            if ($value !== null && $value !== '') {
                $planQuotas[(int) $planId] = (int) $value;
            }
        }

        $blank = function ($value) {
            return $value === null || $value === '';
        };

        settings('ai_assistant', [
            'default_quota' => $blank($request->input('default_quota')) ? '' : (int) $request->input('default_quota'),
            'monthly_budget_usd' => $blank($request->input('monthly_budget_usd')) ? '' : (float) $request->input('monthly_budget_usd'),
            'plan_quotas' => $planQuotas,
        ]);

        return redirect()->route('admin.ai_assistant.index')->with('success', trans('front.successfully_saved'));
    }
}
