@extends('Admin.Layouts.default')

@section('content')
    <style>
        .ab-kpis{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:4px}
        .ab-kpi{flex:1 1 160px;padding:12px 14px;border:1px solid #e3e7ee;border-radius:10px;background:#fff}
        .ab-kpi .v{font-size:22px;font-weight:700}
        .ab-kpi .l{color:#6b7280;font-size:12px}
        .ab-bars{display:flex;align-items:flex-end;gap:3px;height:90px;padding-top:6px}
        .ab-bars div{flex:1;background:#1f6feb;border-radius:3px 3px 0 0;min-height:2px}
        .ab-muted{color:#6b7280}
    </style>

    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="panel-title">AirBot (IA) · {{ date('m/Y') }}</div>
        </div>
        <div class="panel-body">
            @if (!$configured)
                <div class="alert alert-warning">El asistente no está activo: falta <code>ANTHROPIC_API_KEY</code> en <code>.env</code> o <code>AI_ENABLED=false</code>.</div>
            @endif

            <div class="ab-kpis">
                <div class="ab-kpi"><div class="v">USD {{ number_format($totalCost, 2) }}</div><div class="l">Costo del mes</div></div>
                <div class="ab-kpi"><div class="v">{{ ($byFeature['chat']->calls ?? 0) + ($byFeature['explain_report']->calls ?? 0) }}</div><div class="l">Preguntas del mes (chat + explicar reporte)</div></div>
                <div class="ab-kpi"><div class="v">{{ $usersWithAccess }} · {{ count($plansWithAccess) }}</div><div class="l">Usuarios · planes con acceso</div></div>
                <div class="ab-kpi"><div class="v">{{ $totalErrors }}</div><div class="l">Errores del mes</div></div>
            </div>
            <p class="ab-muted" style="margin:6px 0 0">Modelo del chat: <code>{{ $model }}</code>. El acceso se da con el permiso "Asistente IA (AirBot)" del usuario o del plan.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><div class="panel-title">Consumo por función</div></div>
                <div class="panel-body">
                    <table class="table table-condensed">
                        <thead><tr><th>Función</th><th class="text-right">Llamadas</th><th class="text-right">USD</th></tr></thead>
                        <tbody>
                        @foreach ($features as $key => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-right">{{ $byFeature[$key]->calls ?? 0 }}</td>
                                <td class="text-right">{{ number_format($byFeature[$key]->cost ?? 0, 3) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <p class="ab-muted" style="margin:12px 0 0">Costo diario, últimos 30 días</p>
                    @php $max = max(0.0001, (float) $daily->max('cost')); @endphp
                    <div class="ab-bars" title="Costo diario">
                        @foreach ($daily as $d)
                            <div style="height: {{ max(2, round($d->cost / $max * 100)) }}%" title="{{ $d->day }}: USD {{ number_format($d->cost, 3) }} · {{ $d->calls }} llamadas"></div>
                        @endforeach
                    </div>
                    @if ($daily->isEmpty())<p class="ab-muted">Sin consumo todavía.</p>@endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><div class="panel-title">Usuarios con más consumo (mes)</div></div>
                <div class="panel-body">
                    <table class="table table-condensed">
                        <thead><tr><th>Usuario</th><th class="text-right">Preguntas</th><th class="text-right">USD</th></tr></thead>
                        <tbody>
                        @forelse ($byUser as $row)
                            <tr><td>{{ $row->email ?? '-' }}</td><td class="text-right">{{ $row->questions }}</td><td class="text-right">{{ number_format($row->cost, 3) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="ab-muted">Sin consumo todavía.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><div class="panel-title">Cuotas</div></div>
        <div class="panel-body">
            {!! Form::open(['route' => 'admin.ai_assistant.save', 'method' => 'POST']) !!}
            <div class="row">
                <div class="col-sm-4 form-group">
                    <label>Preguntas al mes por usuario (por defecto)</label>
                    <input type="number" min="0" name="default_quota" class="form-control"
                           value="{{ $settings['default_quota'] ?? '' }}" placeholder="{{ $defaults['quota'] }} (de .env; 0 = sin límite)">
                </div>
                <div class="col-sm-4 form-group">
                    <label>Tope de gasto mensual (USD, toda la instalación)</label>
                    <input type="number" min="0" step="0.01" name="monthly_budget_usd" class="form-control"
                           value="{{ $settings['monthly_budget_usd'] ?? '' }}" placeholder="{{ $defaults['budget'] }} (0 = sin tope)">
                </div>
            </div>

            @if ($plans->count())
                <label>Preguntas al mes por plan (vacío = usa el valor por defecto)</label>
                <table class="table table-condensed" style="max-width:640px">
                    <thead><tr><th>Plan</th><th>Acceso a AirBot</th><th style="width:180px">Preguntas/mes</th></tr></thead>
                    <tbody>
                    @foreach ($plans as $plan)
                        <tr>
                            <td>{{ $plan->title }}</td>
                            <td>{!! in_array($plan->id, $plansWithAccess) ? 'Sí' : '<span class="ab-muted">No</span>' !!}</td>
                            <td><input type="number" min="0" name="plan_quotas[{{ $plan->id }}]" class="form-control input-sm"
                                       value="{{ $settings['plan_quotas'][$plan->id] ?? '' }}"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif

            <button type="submit" class="btn btn-primary">{{ trans('global.save') }}</button>
            {!! Form::close() !!}
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><div class="panel-title">Acciones confirmadas por los usuarios (últimas 50)</div></div>
        <div class="panel-body">
            <table class="table table-condensed">
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Estado</th><th>Resultado</th></tr></thead>
                <tbody>
                @forelse ($actions as $a)
                    <tr>
                        <td>{{ $a->created_at }}</td>
                        <td>{{ $a->user->email ?? $a->user_id }}</td>
                        <td>{{ $a->title }}</td>
                        <td>{{ ['done' => 'Hecha', 'failed' => 'Falló', 'cancelled' => 'Cancelada'][$a->status] ?? $a->status }}</td>
                        <td class="ab-muted">{{ \Illuminate\Support\Str::limit($a->result, 90) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="ab-muted">Todavía no hay acciones.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
