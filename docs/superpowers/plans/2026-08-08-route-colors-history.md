# Colores del recorrido — Historial: Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Configuración por vehículo del color de la polilínea del historial con 5 modos (viajes/único/velocidad/sensor-placeholder/horario), editable en el tab Avanzado del modal de device y consumida por el visor de historial.

**Architecture:** El backend solo persiste 5 columnas nuevas en `devices` (flujo estándar del modal: mass-assignment vía `$fillable`) y expone la config al visor como `window.history_route`. TODO el cálculo de color se hace en el frontend (`history.js`), que ya dibuja la ruta segmentada por color (`position.c`): solo se sobreescribe `position.c` por posición según el modo. Spec aprobado: `docs/superpowers/specs/2026-08-08-route-colors-history-design.md`.

**Tech Stack:** Laravel legacy (vistas Blade en `Tobuli/Views/`), jQuery + Bootstrap 3, Leaflet, Gulp 4 (`npx gulp script-app` recompila `public/assets/js/app.js`), PHPUnit legacy (`tests/TestCase.php` global, sin namespace).

**Convenciones:** Textos de UI nuevos hardcodeados en español (el proyecto es un fork es-PE; se pueden mover a lang files después). Commits pequeños por tarea, en la rama `feature/route-colors-history`.

---

## Task 1: Migración — columnas `route_*` en `devices`

**Files:**
- Create: `database/migrations/2026_08_08_000000_add_route_color_to_devices.php`

- [ ] **Step 1: Escribir la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración por vehículo del color de la polilínea del historial
 * ("Colores del recorrido", estilo Wialon).
 *
 * - route_color_type: modo activo (trips | single | speed | sensor | schedule)
 * - route_color: hex del modo "único"
 * - route_speed_ranges: JSON [{"from":0,"to":40,"color":"#22d3ee"},...] (último to=null)
 * - route_sensor_id: reservado para el futuro modo sensor (sin lógica aún)
 * - route_schedule: JSON {"day_from":"06:00","day_to":"18:00","day_color":"#2563eb","night_color":"#000000"}
 */
class AddRouteColorToDevices extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('devices', 'route_color_type')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->string('route_color_type', 10)
                ->default('trips')
                ->after('jimi_model')
                ->comment('Modo de color de la ruta del historial: trips|single|speed|sensor|schedule');
            $table->string('route_color', 10)
                ->nullable()
                ->default(null)
                ->after('route_color_type')
                ->comment('Color hex del modo single');
            $table->text('route_speed_ranges')
                ->nullable()
                ->after('route_color')
                ->comment('JSON de rangos de velocidad [{from,to,color},...]');
            $table->integer('route_sensor_id')
                ->nullable()
                ->default(null)
                ->after('route_speed_ranges')
                ->comment('Reservado: sensor con rangos de colores (modo sensor)');
            $table->text('route_schedule')
                ->nullable()
                ->after('route_sensor_id')
                ->comment('JSON horario {day_from,day_to,day_color,night_color}');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('devices', 'route_color_type')) {
            return;
        }

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'route_color_type',
                'route_color',
                'route_speed_ranges',
                'route_sensor_id',
                'route_schedule',
            ]);
        });
    }
}
```

- [ ] **Step 2: Ejecutar la migración**

Run: `php artisan migrate`
Expected: `Migrating: 2026_08_08_000000_add_route_color_to_devices` → `Migrated`.

- [ ] **Step 3: Verificar columnas y default**

Run: `php artisan tinker --execute="var_dump(Schema::hasColumn('devices','route_schedule')); var_dump(Tobuli\Entities\Device::query()->value('route_color_type'));"`
Expected: `bool(true)` y `string(5) "trips"` (o `NULL` si la tabla está vacía — basta el `true`).

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_08_08_000000_add_route_color_to_devices.php
git commit -m "feat(devices): columnas route_* para colores del recorrido del historial"
```

---

## Task 2: `Device` — `$fillable` + `getRouteConfig()` (TDD)

**Files:**
- Modify: `Tobuli/Entities/Device.php:152-154` (fin del `$fillable`) y método nuevo
- Test: `tests/Unit/DeviceRouteConfigTest.php`

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/Unit/DeviceRouteConfigTest.php` (sin namespace, como los demás tests legacy):

```php
<?php

use Tobuli\Entities\Device;

class DeviceRouteConfigTest extends TestCase
{
    public function testDefaultsToTripsWhenUnset()
    {
        $config = (new Device())->getRouteConfig();

        $this->assertEquals('trips', $config['type']);
        $this->assertNull($config['color']);
        $this->assertNull($config['ranges']);
        $this->assertNull($config['schedule']);
    }

    public function testInvalidTypeFallsBackToTrips()
    {
        $device = new Device();
        $device->route_color_type = 'banana';

        $this->assertEquals('trips', $device->getRouteConfig()['type']);
    }

    public function testCorruptJsonDecodesToNull()
    {
        $device = new Device([
            'route_color_type'   => 'speed',
            'route_speed_ranges' => '{not valid json',
            'route_schedule'     => '[broken',
        ]);

        $config = $device->getRouteConfig();

        $this->assertEquals('speed', $config['type']);
        $this->assertNull($config['ranges']);
        $this->assertNull($config['schedule']);
    }

    public function testValidConfigPassesThrough()
    {
        $device = new Device([
            'route_color_type'   => 'single',
            'route_color'        => '#2563eb',
            'route_speed_ranges' => '[{"from":0,"to":40,"color":"#22d3ee"},{"from":40,"to":null,"color":"#ef4444"}]',
            'route_schedule'     => '{"day_from":"06:00","day_to":"18:00","day_color":"#2563eb","night_color":"#000000"}',
        ]);

        $config = $device->getRouteConfig();

        $this->assertEquals('single', $config['type']);
        $this->assertEquals('#2563eb', $config['color']);
        $this->assertCount(2, $config['ranges']);
        $this->assertEquals(40, $config['ranges'][1]->from);
        $this->assertEquals('06:00', $config['schedule']->day_from);
    }
}
```

- [ ] **Step 2: Correr el test y verificar que falla**

Run: `vendor\bin\phpunit tests/Unit/DeviceRouteConfigTest.php`
Expected: FAIL — `Call to undefined method Tobuli\Entities\Device::getRouteConfig()`.

- [ ] **Step 3: Implementar `$fillable` + método**

En `Tobuli/Entities/Device.php`, al final del array `$fillable` (después de `'jimi_model',` línea ~153):

```php
        'jimi_type',
        'jimi_model',
        'route_color_type',
        'route_color',
        'route_speed_ranges',
        'route_sensor_id',
        'route_schedule',
    );
```

Y añadir el método (por ejemplo debajo de la declaración de `$hidden`, línea ~202, junto a los demás helpers):

```php
    /**
     * Config de color de la ruta del historial para el visor.
     * JSON corrupto => null (el frontend aplica sus defaults).
     */
    public function getRouteConfig(): array
    {
        $type = $this->route_color_type ?: 'trips';

        if (!in_array($type, ['trips', 'single', 'speed', 'sensor', 'schedule'])) {
            $type = 'trips';
        }

        return [
            'type'     => $type,
            'color'    => $this->route_color,
            'ranges'   => json_decode((string) $this->route_speed_ranges) ?: null,
            'schedule' => json_decode((string) $this->route_schedule) ?: null,
        ];
    }
```

- [ ] **Step 4: Correr el test y verificar que pasa**

Run: `vendor\bin\phpunit tests/Unit/DeviceRouteConfigTest.php`
Expected: PASS (4 tests, ~10 assertions).

- [ ] **Step 5: Commit**

```bash
git add Tobuli/Entities/Device.php tests/Unit/DeviceRouteConfigTest.php
git commit -m "feat(devices): fillable + getRouteConfig() para colores del recorrido"
```

---

## Task 3: Validación del form de device

**Files:**
- Modify: `Tobuli/Validation/DeviceFormValidator.php:12-61` (arrays `create` **y** `update`)

- [ ] **Step 1: Añadir reglas en AMBOS arrays**

En `$rules['create']` y en `$rules['update']`, después de `'fuel_detect_sec_after_stop' => ...` en cada uno:

```php
            'fuel_detect_sec_after_stop' => 'nullable|numeric|min:60|max:300',
            'route_color_type'    => 'sometimes|in:trips,single,speed,sensor,schedule',
            'route_color'         => 'sometimes|nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'route_speed_ranges'  => 'sometimes|nullable|string',
            'route_schedule'      => 'sometimes|nullable|string',
```

(Nota: hex estricto en vez de la regla custom `css_color` porque la columna es VARCHAR(10) y `css_color` admite valores más largos — rgba(), nombres CSS — que truncarían o fallarían en DB; el `<input type="color">` del editor solo emite `#rrggbb`. Los JSON solo se validan como string: el frontend cae a defaults ante JSON corrupto, según el spec.)

- [ ] **Step 2: Verificación rápida de sintaxis**

Run: `php -l Tobuli/Validation/DeviceFormValidator.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add Tobuli/Validation/DeviceFormValidator.php
git commit -m "feat(devices): validacion de campos route_* en create/update"
```

---

## Task 4: Puente historial — `window.history_route`

**Files:**
- Modify: `ModalHelpers/HistoryModalHelper.php:286-289` (retorno de `get()`)
- Modify: `Tobuli/Views/Frontend/History/index.blade.php:48-59` (ambas ramas del `@if`)

- [ ] **Step 1: Añadir `route` al retorno de `get()`**

En `ModalHelpers/HistoryModalHelper.php`, el retorno actual (línea ~286):

```php
        return [
            'items' => $items,
            'sensors' => $sensors,
```

queda:

```php
        return [
            'items' => $items,
            'sensors' => $sensors,
            'route' => $device->getRouteConfig(),
```

- [ ] **Step 2: Emitir `window.history_route` en la vista**

En `Tobuli/Views/Frontend/History/index.blade.php`, el bloque `<script>` de la rama con datos (líneas 48-52) queda:

```blade
    <script>
        window.history_items = {!!json_encode($items)!!};
        window.history_sensors = {!!json_encode($sensors)!!};
        window.history_route = {!! json_encode($route ?? null) !!};
        initComponents($('.history'));
    </script>
```

y la rama `@else` (líneas 56-59) queda:

```blade
    <script>
        window.history_items = null;
        window.history_sensors = null;
        window.history_route = null;
    </script>
```

- [ ] **Step 3: Verificación**

Run: `php -l ModalHelpers/HistoryModalHelper.php`
Expected: `No syntax errors detected`.

Manual (opcional aquí, obligatorio en Task 8): abrir el historial de un device en el navegador y en la consola comprobar `window.history_route` → `{type: "trips", color: null, ranges: null, schedule: null}`.

- [ ] **Step 4: Commit**

```bash
git add ModalHelpers/HistoryModalHelper.php Tobuli/Views/Frontend/History/index.blade.php
git commit -m "feat(history): exponer config route del device como window.history_route"
```

---

## Task 5: UI del editor — bloque en el tab Avanzado

**Files:**
- Modify: `Tobuli/Views/Frontend/Devices/partials/advanced.blade.php` (añadir al FINAL del archivo, después del form-group de `timezone_id`, línea 273)

**Contexto imprescindible:** el modal se carga por AJAX y el partial se inyecta con `$.html()`, que SÍ ejecuta los `<script>` inline — por eso la IIFE va dentro del partial. El tema legacy estiliza `.radio` con el input absoluto cubriendo el contenedor: el radio+label van SOLOS en su div y los controles FUERA, en la misma fila flex.

- [ ] **Step 1: Añadir el bloque completo al final de `advanced.blade.php`**

```blade

@php
    $routeColorType = in_array($item->route_color_type, ['trips', 'single', 'speed', 'schedule'])
        ? $item->route_color_type
        : 'trips';
    $routeColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $item->route_color)
        ? $item->route_color
        : '#2563eb';
    // json_decode + json_encode: si el JSON guardado está corrupto, al JS llega null (usa defaults)
    $routeRangesJson = json_encode(json_decode((string) $item->route_speed_ranges) ?: null);
    $routeScheduleJson = json_encode(json_decode((string) $item->route_schedule) ?: null);
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
            <input type="radio" name="route_color_type" value="sensor" id="rct_sensor" disabled>
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
    var schedule = $.extend({}, DEFAULT_SCHEDULE, initialSchedule || {});

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
                $('<span/>').text('\u221E').css({ width: '70px', 'text-align': 'center' }).appendTo($row);
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
```

- [ ] **Step 2: Verificación en el navegador**

1. Abrir la app, editar un device → tab "Avanzado". Al final debe verse el bloque con los 5 radios, la barra de preview con los 5 colores default y las 5 filas de rangos.
2. "Por sensor" debe estar gris y no seleccionable.
3. Cambiar el `to` de un rango → el radio "Por velocidad" se auto-selecciona y la barra se redibuja.
4. Botón `+` → aparece un rango nuevo antes del ∞; botón `↺` → vuelven los 5 defaults.
5. Cambiar hora/color del horario → se auto-selecciona "Por horario".
6. Elegir "Único" con un color, guardar, reabrir el modal → el radio y el color persisten (verifica también en DB: `php artisan tinker --execute="echo Tobuli\Entities\Device::find(<ID>)->route_color_type;"`).

- [ ] **Step 3: Commit**

```bash
git add Tobuli/Views/Frontend/Devices/partials/advanced.blade.php
git commit -m "feat(devices): UI de colores del recorrido en tab Avanzado del modal"
```

---

## Task 6: `history.js` — color por posición + leyenda

**Files:**
- Modify: `resources/assets/js/controller/history.js` (helpers nuevos antes de `_this.parse` línea ~375; 3 inserciones dentro de `parse()`; 1 inserción en `clear()` línea ~289)

- [ ] **Step 1: Añadir constantes y helpers**

Insertar ANTES de la línea `  _this.parse = function () {` (línea ~375):

```js
  // --- Colores del recorrido (config por vehículo, window.history_route) ---
  var ROUTE_TRIP_PALETTE = [
    "#2563eb", "#16a34a", "#0891b2", "#7c3aed", "#db2777",
    "#ea580c", "#ca8a04", "#0d9488", "#9333ea", "#dc2626",
  ];
  var ROUTE_STOP_COLOR = "#94a3b8";
  var ROUTE_SINGLE_DEFAULT = "#2563eb";
  var ROUTE_DEFAULT_RANGES = [
    { from: 0, to: 40, color: "#22d3ee" },
    { from: 40, to: 60, color: "#3b82f6" },
    { from: 60, to: 90, color: "#6366f1" },
    { from: 90, to: 120, color: "#a855f7" },
    { from: 120, to: null, color: "#ef4444" },
  ];
  var ROUTE_DEFAULT_SCHEDULE = {
    day_from: "06:00",
    day_to: "18:00",
    day_color: "#2563eb",
    night_color: "#000000",
  };

  _this.routeLegend = null;

  _this.routeMode = function () {
    var route = window.history_route || null;

    if (!route || !route.type) return "trips";

    // el modo sensor aún no existe: cae a speed
    var type = route.type === "sensor" ? "speed" : route.type;

    if (["trips", "single", "speed", "schedule"].indexOf(type) < 0) type = "trips";

    return type;
  };

  _this.routeRanges = function () {
    var route = window.history_route || {};
    var ranges = route.ranges;

    if (!$.isArray(ranges) || !ranges.length) return ROUTE_DEFAULT_RANGES;

    for (var i = 0; i < ranges.length; i++) {
      if (typeof ranges[i].from !== "number" || !ranges[i].color) return ROUTE_DEFAULT_RANGES;
    }

    return ranges;
  };

  _this.routeSchedule = function () {
    var route = window.history_route || {};
    var s = route.schedule;

    if (!s || !/^\d\d:\d\d$/.test(s.day_from || "") || !/^\d\d:\d\d$/.test(s.day_to || ""))
      return ROUTE_DEFAULT_SCHEDULE;

    return {
      day_from: s.day_from,
      day_to: s.day_to,
      day_color: s.day_color || ROUTE_DEFAULT_SCHEDULE.day_color,
      night_color: s.night_color || ROUTE_DEFAULT_SCHEDULE.night_color,
    };
  };

  _this.routeColorAt = function (mode, position, itemColor) {
    if (mode === "single") {
      return (window.history_route && window.history_route.color) || ROUTE_SINGLE_DEFAULT;
    }

    if (mode === "speed") {
      var spd = parseFloat(position.s) || 0;
      var ranges = _this.routeRanges();

      for (var i = 0; i < ranges.length; i++) {
        if (ranges[i].to === null || typeof ranges[i].to === "undefined" || spd < ranges[i].to)
          return ranges[i].color;
      }

      return ranges[ranges.length - 1].color;
    }

    if (mode === "schedule") {
      var s = _this.routeSchedule();
      var hm = (position.t || "").substr(11, 5); // position.t ya viene en la TZ del usuario
      var day;

      if (s.day_from <= s.day_to) {
        day = hm >= s.day_from && hm < s.day_to;
      } else {
        // rango que cruza medianoche, ej. 20:00 -> 06:00
        day = hm >= s.day_from || hm < s.day_to;
      }

      return day ? s.day_color : s.night_color;
    }

    // trips (y cualquier fallback): color por item; si no hay, el del backend
    return itemColor || position.c;
  };

  _this.routeLegendAdd = function () {
    _this.routeLegendRemove();

    if (_this.routeMode() !== "speed") return;

    var ranges = _this.routeRanges();
    var scale = ranges.length > 1 ? ranges[ranges.length - 1].from : 100;
    var bar = '<div style="display:flex;border-radius:2px;overflow:hidden;">';
    var labels = '<div style="display:flex;font-size:10px;color:#333;">';

    $.each(ranges, function (i, r) {
      var w = r.to === null ? 18 : Math.max(6, ((r.to - r.from) / scale) * 82);

      bar += '<div style="width:' + w + '%;height:8px;background:' + r.color + ';"></div>';
      labels +=
        '<div style="width:' + w + '%;">' + r.from +
        (r.to === null ? '<span style="float:right;">&#8734;</span>' : "") +
        "</div>";
    });

    bar += "</div>";
    labels += "</div>";

    var legend = L.control({ position: "bottomleft" });

    legend.onAdd = function () {
      var div = L.DomUtil.create("div", "history-route-legend");

      div.style.background = "rgba(255,255,255,0.9)";
      div.style.padding = "4px 8px";
      div.style.borderRadius = "4px";
      div.style.width = "220px";
      div.style.boxShadow = "0 1px 4px rgba(0,0,0,0.3)";
      div.innerHTML = bar + labels;

      return div;
    };

    legend.addTo(app.map);
    _this.routeLegend = legend;
  };

  _this.routeLegendRemove = function () {
    if (_this.routeLegend) {
      app.map.removeControl(_this.routeLegend);
      _this.routeLegend = null;
    }
  };

```

- [ ] **Step 2: Integrar en `parse()` (3 inserciones)**

**(a)** Tras las declaraciones locales (línea ~393), el bloque:

```js
      let polyArray = [],
        polylines = L.featureGroup(),
        poly = null,
        markers = {},
        lastColor = null,
        lastDrive = null,
        lastStop = null;
```

queda:

```js
      let polyArray = [],
        polylines = L.featureGroup(),
        poly = null,
        markers = {},
        lastColor = null,
        lastDrive = null,
        lastStop = null;

      var routeMode = _this.routeMode(),
        routeDriveCount = 0;
```

**(b)** Al inicio del callback del bucle de items (línea ~395), el bloque:

```js
      $.each(window.history_items, function (index, item) {
        if (typeof item.positions !== "undefined") {
```

queda:

```js
      $.each(window.history_items, function (index, item) {
        var itemColor = null;

        if (routeMode === "trips" && typeof item.positions !== "undefined") {
          if (item.status == 1) {
            itemColor = ROUTE_TRIP_PALETTE[routeDriveCount % ROUTE_TRIP_PALETTE.length];
            routeDriveCount++;
          } else {
            itemColor = ROUTE_STOP_COLOR;
          }
        }

        if (typeof item.positions !== "undefined") {
```

**(c)** Dentro del bucle de posiciones, tras construir `point` (línea ~404), el bloque:

```js
            let point = {
              lat: parseFloat(position.lat),
              lng: parseFloat(position.lng),
            };
```

queda:

```js
            let point = {
              lat: parseFloat(position.lat),
              lng: parseFloat(position.lng),
            };

            position.c = _this.routeColorAt(routeMode, position, itemColor);
```

(El corte de polilínea por cambio de color de las líneas siguientes — `lastColor !== position.c` — queda intacto y hace todo el trabajo de segmentación.)

**(d)** Al final del bloque con datos, la línea (~491):

```js
      _this.polylines.addTo(app.map);
```

queda:

```js
      _this.polylines.addTo(app.map);

      _this.routeLegendAdd();
```

- [ ] **Step 3: Integrar en `clear()`**

La línea (~289):

```js
    _this.polylinePoints.clearLayers();
```

queda:

```js
    _this.polylinePoints.clearLayers();

    _this.routeLegendRemove();
```

- [ ] **Step 4: Verificación de sintaxis**

Run: `node --check resources/assets/js/controller/history.js`
Expected: sin salida (exit 0).

- [ ] **Step 5: Commit**

```bash
git add resources/assets/js/controller/history.js
git commit -m "feat(history): color de ruta por modo del vehiculo + leyenda de velocidad"
```

---

## Task 7: Recompilar el bundle `app.js`

**Files:**
- Modify (generado): `public/assets/js/app.js`

- [ ] **Step 1: Compilar**

Run: `npx gulp script-app`
Expected: `Finished 'script-app'` sin errores.

- [ ] **Step 2: Verificar que el bundle contiene el código nuevo**

Run: `git status --short public/assets/js/app.js` → debe aparecer como modificado.
Grep `routeColorAt` en `public/assets/js/app.js` → al menos 1 coincidencia.

- [ ] **Step 3: Commit**

```bash
git add public/assets/js/app.js
git commit -m "build: recompilar bundle app.js"
```

---

## Task 8: Verificación end-to-end (criterios de aceptación del spec)

Manual, en el navegador (con al menos un device con historial del día):

- [ ] 1. Device sin configurar → historial "por viajes": cada tramo drive un color distinto de la paleta, paradas en gris `#94a3b8`, sin errores en consola. Flechas de dirección y popups funcionan igual que antes.
- [ ] 2. "Único" (`#ff0000` p.ej.) → guardar → recargar historial → toda la ruta roja. Reabrir modal → persiste.
- [ ] 3. "Por velocidad" con defaults → la ruta cambia de color según velocidad y aparece la leyenda abajo-izquierda con ticks `0 40 60 90 120 ∞`. Editar rangos (añadir/borrar/editar `to`) → rangos siempre contiguos desde 0 con último ∞ → guardar → historial refleja los nuevos colores.
- [ ] 4. "Por horario" con defaults → posiciones entre 06:00–18:00 (hora del usuario) en azul, resto en negro. Probar un rango que cruce medianoche (`20:00`–`06:00`). Reabrir modal → persiste.
- [ ] 5. JSON corrupto: `php artisan tinker --execute="Tobuli\Entities\Device::find(<ID>)->update(['route_speed_ranges' => '{roto']);"` con modo `speed` → el historial pinta con los 5 defaults, sin errores.
- [ ] 6. "Por sensor" sigue deshabilitado; la leyenda NO aparece en modos ≠ velocidad; al limpiar/cambiar de tab la leyenda desaparece (`clear()`).
- [ ] 7. Suite PHP: `vendor\bin\phpunit tests/Unit/DeviceRouteConfigTest.php` → PASS.

- [ ] **Commit final (si hubo ajustes) y cierre de rama** — usar el skill superpowers:finishing-a-development-branch.
