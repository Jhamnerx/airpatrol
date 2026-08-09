<div class="form-group">
    {!! Form::label('group_id', trans('validation.attributes.group_id') . ':') !!}
    {!! Form::select('group_id', $device_groups, $group_id ?? null, [
        'class' => 'form-control',
        'data-live-search' => 'true',
    ]) !!}
</div>

@if (Auth::user()->can('view', $item, 'device_type_id'))
    <div class="form-group">
        {!! Form::label('device_type_id', trans('validation.attributes.device_type_id') . ':') !!}
        {!! Form::select(
            'device_type_id',
            $device_types,
            $item->device_type_id,
            ['class' => 'form-control'] +
                (!Auth::user()->can('edit', $item, 'device_type_id') ? ['disabled' => 'disabled'] : []),
        ) !!}
    </div>
@endif

@if (Auth::user()->can('view', $item, 'authentication'))
    <div class="form-group">
        {!! Form::label('authentication', trans('validation.attributes.authentication') . ':') !!}
        {!! Form::text(
            'authentication',
            $item->authentication,
            ['class' => 'form-control'] + (Auth::user()->can('edit', $item, 'authentication') ? [] : ['disabled']),
        ) !!}
    </div>
@endif

@if (Auth::user()->can('view', $item, 'jimi_type'))
    <div class="form-group">
        {!! Form::label('jimi_type', 'Jimi IoT:') !!}
        {!! Form::select(
            'jimi_type',
            [
                '' => trans('global.no') . ' (No Jimi)',
                \Tobuli\Entities\Device::JIMI_TYPE_GPS => 'GPS',
                \Tobuli\Entities\Device::JIMI_TYPE_VIDEO => 'GPS + Video',
            ],
            $item->jimi_type,
            ['class' => 'form-control', 'id' => 'jimi_type'] +
                (Auth::user()->can('edit', $item, 'jimi_type') ? [] : ['disabled' => 'disabled']),
        ) !!}
    </div>
@endif

@php $additional_fields_on = settings('plugins.additional_installation_fields.status') @endphp

<div class="row">
    <div class="col-sm-6">
        @if (Auth::user()->can('view', $item, 'sim_number') && empty($multiItems))
            <div class="form-group">
                {!! Form::label('sim_number', trans('validation.attributes.sim_number') . ':') !!}
                {!! Form::text(
                    'sim_number',
                    $item->sim_number,
                    ['class' => 'form-control'] + (!Auth::user()->can('edit', $item, 'sim_number') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif

        @if (settings('plugins.sim_blocking.status') && Auth::user()->can('view', $item, 'msisdn'))
            <div class="form-group">
                {!! Form::label('msisdn', trans('validation.attributes.msisdn') . ':') !!}
                {!! Form::text(
                    'msisdn',
                    $item->msisdn,
                    ['class' => 'form-control'] + (!Auth::user()->can('edit', $item, 'msisdn') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif

        @if ($additional_fields_on && Auth::user()->can('view', $item, 'sim_activation_date'))
            <div class="form-group">
                {!! Form::label('sim_activation_date', trans('validation.attributes.sim_activation_date') . ':') !!}
                {!! Form::text(
                    'sim_activation_date',
                    $item->sim_activation_date == '0000-00-00' ? null : $item->sim_activation_date,
                    ['class' => 'form-control datepicker'] +
                        (!Auth::user()->can('edit', $item, 'sim_activation_date') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif

        @if ($additional_fields_on && Auth::user()->can('view', $item, 'sim_expiration_date'))
            <div class="form-group">
                {!! Form::label('sim_expiration_date', trans('validation.attributes.sim_expiration_date') . ':') !!}
                {!! Form::text(
                    'sim_expiration_date',
                    $item->sim_expiration_date == '0000-00-00' ? null : $item->sim_expiration_date,
                    ['class' => 'form-control datepicker'] +
                        (!Auth::user()->can('edit', $item, 'sim_expiration_date') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif
        @if (Auth::user()->can('view', $item, 'vin'))
            <div class="form-group">
                {!! Form::label('vin', trans('validation.attributes.vin') . ':') !!}
                {!! Form::text(
                    'vin',
                    $item->vin,
                    ['class' => 'form-control'] + (!Auth::user()->can('edit', $item, 'vin') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif
        @if (Auth::user()->can('view', $item, 'device_model'))
            <div class="form-group">
                {!! Form::label('device_model', trans('validation.attributes.device_model') . ':') !!}
                {!! Form::text(
                    'device_model',
                    $item->device_model,
                    ['class' => 'form-control'] + (!Auth::user()->can('edit', $item, 'device_model') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif
    </div>
    <div class="col-sm-6">
        @if ($additional_fields_on && Auth::user()->can('view', $item, 'installation_date'))
            <div class="form-group">
                {!! Form::label('installation_date', trans('validation.attributes.installation_date') . ':') !!}
                {!! Form::text(
                    'installation_date',
                    $item->installation_date == '0000-00-00' ? null : $item->installation_date,
                    ['class' => 'form-control datepicker'] +
                        (!Auth::user()->can('edit', $item, 'installation_date') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif
        @if (Auth::user()->can('view', $item, 'plate_number'))
            <div class="form-group">
                {!! Form::label('plate_number', trans('validation.attributes.plate_number') . ':') !!}
                {!! Form::text(
                    'plate_number',
                    $item->plate_number,
                    ['class' => 'form-control'] + (!Auth::user()->can('edit', $item, 'plate_number') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif
        @if (Auth::user()->can('view', $item, 'registration_number'))
            <div class="form-group">
                {!! Form::label('registration_number', trans('validation.attributes.registration_number') . ':') !!}
                {!! Form::text(
                    'registration_number',
                    $item->registration_number,
                    ['class' => 'form-control'] +
                        (!Auth::user()->can('edit', $item, 'registration_number') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif
        @if (Auth::user()->can('view', $item, 'object_owner'))
            <div class="form-group">
                {!! Form::label('object_owner', trans('validation.attributes.object_owner') . ':') !!}
                {!! Form::text(
                    'object_owner',
                    $item->object_owner,
                    ['class' => 'form-control'] + (!Auth::user()->can('edit', $item, 'object_owner') ? ['disabled' => 'disabled'] : []),
                ) !!}
            </div>
        @endif
    </div>
</div>
@if (Auth::user()->can('view', $item, 'additional_notes'))
    <div class="form-group">
        {!! Form::label('additional_notes', trans('validation.attributes.additional_notes') . ':') !!}
        {!! Form::text(
            'additional_notes',
            $item->additional_notes,
            ['class' => 'form-control'] +
                (!Auth::user()->can('edit', $item, 'additional_notes') ? ['disabled' => 'disabled'] : []),
        ) !!}
    </div>
@endif
@if (Auth::user()->can('view', $item, 'comment'))
    <div class="form-group">
        {!! Form::label('comment', trans('validation.attributes.comment') . ':') !!}
        {!! Form::text(
            'comment',
            $item->comment,
            ['class' => 'form-control'] + (!Auth::user()->can('edit', $item, 'comment') ? ['disabled' => 'disabled'] : []),
        ) !!}
    </div>
@endif
@if (config('addon.device_tracker_app_login'))
    <div class="form-group">
        <div class="checkbox-inline">
            {!! Form::hidden('app_tracker_login', 0) !!}
            {!! Form::checkbox('app_tracker_login', 1, $item->app_tracker_login) !!}
            {!! Form::label(null, trans('validation.attributes.app_tracker_login')) !!}
        </div>
    </div>
@endif
<div class="form-group">
    <div class="checkbox">
        {!! Form::hidden('gprs_templates_only', 0) !!}
        {!! Form::checkbox('gprs_templates_only', 1, $item->gprs_templates_only) !!}
        {!! Form::label('gprs_templates_only', trans('validation.attributes.gprs_templates_only')) !!}
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('fuel_measurement_id', trans('validation.attributes.fuel_measurement_type') . ':') !!}
            {!! Form::select('fuel_measurement_id', $device_fuel_measurements_select, $item->fuel_measurement_id, [
                'class' => 'form-control',
            ]) !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="fuel_quantity">
                <span class="fuel_title"></span> {!! trans('front.per') !!} <span class="distance_title"></span>:
            </label>
            {!! Form::text('fuel_quantity', $item->fuel_quantity, [
                'class' => 'form-control',
                'placeholder' => '0.00',
                'id' => 'fuel_quantity',
            ]) !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="fuel_price">
                {!! trans('front.cost_for') !!} <span class="cost_title"></span>:
            </label>
            {!! Form::text('fuel_price', $item->fuel_price, [
                'class' => 'form-control',
                'placeholder' => '0.00',
                'id' => 'fuel_price',
            ]) !!}
        </div>
    </div>
</div>

@if (Auth::user()->can('view', $item, 'forward'))
    <div class="form-group">
        {!! Form::label(null, trans('validation.attributes.forward') . ':') !!}
        <div class="input-group">
            <div class="checkbox input-group-btn">
                {!! Form::hidden('forward[active]', 0) !!}
                {!! Form::checkbox('forward[active]', 1, \Illuminate\Support\Arr::get($item->forward, 'active')) !!}
                {!! Form::label(null) !!}
            </div>
            {!! Form::text(
                'forward[ip]',
                \Illuminate\Support\Arr::get($item->forward, 'ip'),
                array_merge(
                    ['class' => 'form-control', 'placeholder' => '10.0.0.0:6000'],
                    Auth::user()->can('edit', $item, 'forward') ? [] : ['readonly' => true],
                ),
            ) !!}
            <div class="input-group-addon">
                <div class="checkbox-inline">
                    {!! Form::radio('forward[protocol]', 'TCP', \Illuminate\Support\Arr::get($item->forward, 'protocol') != 'UDP') !!}
                    {!! Form::label(null, 'TCP') !!}
                </div>
                <div class="checkbox-inline">
                    {!! Form::radio('forward[protocol]', 'UDP', \Illuminate\Support\Arr::get($item->forward, 'protocol') == 'UDP') !!}
                    {!! Form::label(null, 'UDP') !!}
                </div>
            </div>
        </div>
        <small>{!! trans('front.forward_semicolon') !!}</small>
    </div>
@endif

<div class="form-group">
    {!! Form::label('timezone_id', trans('validation.attributes.time_adjustment') . ':') !!}
    {!! Form::select('timezone_id', $timezones, empty($timezone_id) ? 0 : $timezone_id, ['class' => 'form-control']) !!}
    <small>{!! trans('front.by_default_time') !!}</small>
</div>

@php
    $routeColorType = in_array($item->route_color_type, ['trips', 'single', 'speed', 'sensor', 'schedule'])
        ? $item->route_color_type
        : 'trips';
    $routeColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $item->route_color)
        ? $item->route_color
        : '#2563eb';
    // json_decode + json_encode: si el JSON guardado está corrupto, al JS llega null (usa defaults)
    $routeRangesJson = json_encode(json_decode((string) $item->route_speed_ranges) ?: null, JSON_HEX_TAG);
    $routeScheduleJson = json_encode(json_decode((string) $item->route_schedule) ?: null, JSON_HEX_TAG);
@endphp

<div class="form-group" id="route-color-settings">
    <label>Colores del recorrido — Historial:</label>

    {{-- inicializados server-side: si el JS no llegara a ejecutarse, un guardado no borra la config --}}
    <input type="hidden" name="route_speed_ranges" value="{{ $routeRangesJson === 'null' ? '' : $routeRangesJson }}">
    <input type="hidden" name="route_schedule" value="{{ $routeScheduleJson === 'null' ? '' : $routeScheduleJson }}">

    {{-- 1. Por viajes (default) --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <div class="radio" style="margin:0;">
            <input type="radio" name="route_color_type" value="trips" id="rct_trips" @if($routeColorType == 'trips') checked @endif>
            <label for="rct_trips">Por viajes</label>
        </div>
    </div>

    {{-- 2. Único --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <div class="radio" style="margin:0;">
            <input type="radio" name="route_color_type" value="single" id="rct_single" @if($routeColorType == 'single') checked @endif>
            <label for="rct_single">Único</label>
        </div>
        <input type="color" name="route_color" id="rct_single_color" value="{{ $routeColor }}" style="width:36px;height:24px;padding:0;border:none;">
    </div>

    {{-- 3. Por velocidad --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px;">
        <div class="radio" style="margin:0;">
            <input type="radio" name="route_color_type" value="speed" id="rct_speed" @if($routeColorType == 'speed') checked @endif>
            <label for="rct_speed">Por velocidad</label>
        </div>
        <div id="rct-speed-preview" style="flex:1;display:flex;height:10px;border-radius:2px;overflow:hidden;min-width:80px;"></div>
        <button type="button" id="rct-speed-add" class="btn btn-xs btn-default" title="Añadir rango">+</button>
        <button type="button" id="rct-speed-reset" class="btn btn-xs btn-default" title="Restaurar defaults">&#8634;</button>
    </div>
    <div id="rct-speed-rows" style="margin:0 0 6px 20px;"></div>

    {{-- 4. Por sensor (placeholder deshabilitado) --}}
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <div class="radio disabled" style="margin:0;">
            <input type="radio" name="route_color_type" value="sensor" id="rct_sensor" disabled @if($routeColorType == 'sensor') checked @endif>
            <label for="rct_sensor" style="color:#999;">Por sensor</label>
        </div>
        <small style="color:#999;">(requiere un sensor con rangos de colores por intervalo)</small>
    </div>

    {{-- 5. Por horario --}}
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
        <div class="radio" style="margin:0;">
            <input type="radio" name="route_color_type" value="schedule" id="rct_schedule" @if($routeColorType == 'schedule') checked @endif>
            <label for="rct_schedule">Por horario</label>
        </div>
        <span>Día de</span>
        <input type="time" id="rct_day_from" class="form-control input-sm" style="width:90px;" value="06:00">
        <span>a</span>
        <input type="time" id="rct_day_to" class="form-control input-sm" style="width:90px;" value="18:00">
        <input type="color" id="rct_day_color" value="#2563eb" title="Color diurno" style="width:36px;height:24px;padding:0;border:none;">
        <input type="color" id="rct_night_color" value="#000000" title="Color nocturno" style="width:36px;height:24px;padding:0;border:none;">
    </div>
    <small style="margin-left:20px;color:#777;">La noche es el resto del día; el rango puede cruzar medianoche.</small>
</div>

<script>
(function ($) {
    var DEFAULT_RANGES = [
        { from: 0, to: 40, color: '#22d3ee' },
        { from: 40, to: 60, color: '#3b82f6' },
        { from: 60, to: 90, color: '#6366f1' },
        { from: 90, to: 120, color: '#a855f7' },
        { from: 120, to: null, color: '#ef4444' }
    ];
    var DEFAULT_SCHEDULE = { day_from: '06:00', day_to: '18:00', day_color: '#2563eb', night_color: '#000000' };

    var $wrap = $('#route-color-settings');
    if (!$wrap.length) return;

    var $hiddenRanges = $wrap.find('input[name="route_speed_ranges"]');
    var $hiddenSchedule = $wrap.find('input[name="route_schedule"]');

    function cloneRanges(src) {
        return $.map(src, function (r) { return { from: r.from, to: r.to, color: r.color }; });
    }

    var initialRanges = {!! $routeRangesJson !!};
    var ranges = ($.isArray(initialRanges) && initialRanges.length)
        ? cloneRanges(initialRanges)
        : cloneRanges(DEFAULT_RANGES);

    var initialSchedule = {!! $routeScheduleJson !!};
    var schedule = $.extend({}, DEFAULT_SCHEDULE, $.isPlainObject(initialSchedule) ? initialSchedule : {});

    function normalize() {
        ranges.sort(function (a, b) { return a.from - b.from; });

        var from = 0;
        for (var i = 0; i < ranges.length; i++) {
            ranges[i].from = from;

            if (i === ranges.length - 1) {
                ranges[i].to = null;
                break;
            }

            var to = parseFloat(ranges[i].to);
            if (isNaN(to) || to <= ranges[i].from) to = ranges[i].from + 10;

            ranges[i].to = to;
            from = to;
        }
    }

    function selectSpeed() { $('#rct_speed').prop('checked', true); }
    function selectSchedule() { $('#rct_schedule').prop('checked', true); }

    function render() {
        normalize();
        $hiddenRanges.val(JSON.stringify(ranges));

        var scale = ranges.length > 1 ? ranges[ranges.length - 1].from : 100;

        var $bar = $('#rct-speed-preview').empty();
        $.each(ranges, function (i, r) {
            var w = r.to === null ? 18 : Math.max(6, ((r.to - r.from) / scale) * 82);
            $('<div/>').css({ width: w + '%', background: r.color }).appendTo($bar);
        });

        var $rows = $('#rct-speed-rows').empty();
        $.each(ranges, function (i, r) {
            var $row = $('<div/>').css({ display: 'flex', 'align-items': 'center', gap: '6px', margin: '2px 0' });

            $('<input/>', { type: 'text', disabled: true, 'class': 'form-control input-sm', value: r.from })
                .css('width', '70px').appendTo($row);

            if (r.to === null) {
                $('<span/>').text('∞').css({ width: '70px', 'text-align': 'center' }).appendTo($row);
            } else {
                $('<input/>', { type: 'number', 'class': 'form-control input-sm rct-to', 'data-i': i, value: r.to })
                    .css('width', '70px').appendTo($row);
            }

            $('<input/>', { type: 'color', 'class': 'rct-color', 'data-i': i, value: r.color })
                .css({ width: '36px', height: '24px', padding: 0, border: 'none' }).appendTo($row);

            if (ranges.length > 1) {
                $('<button/>', { type: 'button', 'class': 'btn btn-xs btn-default rct-del', 'data-i': i, html: '&times;' })
                    .appendTo($row);
            }

            $row.appendTo($rows);
        });
    }

    function renderSchedule() {
        $('#rct_day_from').val(schedule.day_from);
        $('#rct_day_to').val(schedule.day_to);
        $('#rct_day_color').val(schedule.day_color);
        $('#rct_night_color').val(schedule.night_color);
        $hiddenSchedule.val(JSON.stringify(schedule));
    }

    $wrap.on('change', '.rct-to', function () {
        ranges[$(this).data('i')].to = parseFloat($(this).val());
        selectSpeed();
        render();
    });

    $wrap.on('change', '.rct-color', function () {
        ranges[$(this).data('i')].color = $(this).val();
        selectSpeed();
        render();
    });

    $wrap.on('click', '.rct-del', function () {
        ranges.splice($(this).data('i'), 1);
        selectSpeed();
        render();
    });

    $('#rct-speed-add').on('click', function () {
        var lastFrom = ranges[ranges.length - 1].from;
        ranges.splice(ranges.length - 1, 0, { from: lastFrom, to: lastFrom + 20, color: '#94a3b8' });
        selectSpeed();
        render();
    });

    $('#rct-speed-reset').on('click', function () {
        ranges = cloneRanges(DEFAULT_RANGES);
        selectSpeed();
        render();
    });

    $('#rct_day_from, #rct_day_to, #rct_day_color, #rct_night_color').on('change', function () {
        schedule.day_from = $('#rct_day_from').val() || DEFAULT_SCHEDULE.day_from;
        schedule.day_to = $('#rct_day_to').val() || DEFAULT_SCHEDULE.day_to;
        schedule.day_color = $('#rct_day_color').val();
        schedule.night_color = $('#rct_night_color').val();
        selectSchedule();
        renderSchedule();
    });

    render();
    renderSchedule();
})(jQuery);
</script>
