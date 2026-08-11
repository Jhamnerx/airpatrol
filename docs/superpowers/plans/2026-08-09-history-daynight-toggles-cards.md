# Toggles Día/Noche + Cards del historial: Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Toggles para ocultar/mostrar tramos diurnos y nocturnos del historial (solo modo horario) + fila de 4 cards informativos (movimiento, detenido, vel. máxima, km) reactivos a lo visible.

**Architecture:** Todo en el frontend sobre el pipeline existente de `history.js` (rama `feature/route-colors-history`, que ya tiene los modos de color). Única línea backend: `'d'` (delta de distancia en km crudos) en el payload de posiciones. Toggle = re-ejecutar `parse()` (determinista); los tramos ocultos cortan la polilínea sin puentear. Cards calculados con `moment.js` (ya en el bundle) y las unidades de `app.settings.units`. Spec aprobado: `docs/superpowers/specs/2026-08-09-history-daynight-toggles-cards-design.md`.

**Tech Stack:** Laravel legacy (Blade en `Tobuli/Views/`), jQuery + Leaflet + moment.js, bundle `public/assets/js/app.js` regenerado por concat plano (NO hay gulp/npm funcional: node-sass@7 incompatible con Node 22 — ver commit `be082f9`). Estilos inline (no hay toolchain CSS).

**Estado actual de `history.js` relevante** (tras la feature anterior): helpers de color en líneas 377-525 (`routeMode` 400, `routeRanges` 413, `routeSchedule` 427, `routeColorAt` 442, leyenda 478-525), `parse()` desde 527 (bucle de posiciones 563-592, marcadores 595-622, `routeLegendAdd()` 661), `clear()` en 264-296 (con `routeLegendRemove()` en 291), `polylinePointsSet` 306-373 (check skipInvalid ~333), `get()` en ~138.

---

## Task 1: Backend — delta de distancia `d` en el payload de posiciones

**Files:**
- Modify: `ModalHelpers/HistoryModalHelper.php` (~línea 221, dentro del `array_map` de posiciones de `get()`)

- [ ] **Step 1: Añadir el campo.** El bloque actual:

```php
                                'id' => $position->id,
                                't' => Formatter::time()->convert($position->time),
                                'a' => Formatter::altitude()->format($position->altitude),
                                's' => Formatter::speed()->format($position->speed),
                                'c' => $position->color,
```

queda:

```php
                                'id' => $position->id,
                                't' => Formatter::time()->convert($position->time),
                                'a' => Formatter::altitude()->format($position->altitude),
                                's' => Formatter::speed()->format($position->speed),
                                'd' => (float) ($position->distance ?? 0),
                                'c' => $position->color,
```

(`$position->distance` lo setea `Tobuli\History\Actions\AppendDistance` — delta en KM crudos respecto de la posición anterior; el frontend convierte con `app.settings.units.distance.radio`. El `?? 0` es defensivo. NO tocar `getApi()` ni `getMessages()`.)

- [ ] **Step 2: Verificar.**

Run: `php -l ModalHelpers/HistoryModalHelper.php` → `No syntax errors detected` (ignorar ruido Imagick/SendGrid en stderr).
Run: `vendor\bin\phpunit tests\Unit\DeviceRouteConfigTest.php` → `OK (5 tests, 15 assertions)`.

- [ ] **Step 3: Commit.**

```bash
git add ModalHelpers/HistoryModalHelper.php
git commit -m "feat(history): delta de distancia 'd' por posicion en el payload del visor"
```

(Añadir al final del mensaje, tras línea en blanco: `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>` — aplica a TODOS los commits de este plan.)

---

## Task 2: `history.js` — clasificación día/noche, estado de toggles y refactor

**Files:**
- Modify: `resources/assets/js/controller/history.js`

- [ ] **Step 1: Extraer `routeIsDay` y añadir estado/helper de franja.** Insertar INMEDIATAMENTE DESPUÉS del cierre de `_this.routeSchedule` (línea ~440, el `};` que sigue a `night_color: ...`):

```js

  _this.routeIsDay = function (position) {
    var s = _this.routeSchedule();
    var hm = (position.t || "").substr(11, 5); // position.t ya viene en la TZ del usuario

    if (s.day_from <= s.day_to) return hm >= s.day_from && hm < s.day_to;

    // rango que cruza medianoche, ej. 20:00 -> 06:00
    return hm >= s.day_from || hm < s.day_to;
  };

  _this.franjaVisible = { day: true, night: true };

  _this.routeFranjaHidden = function (position) {
    if (_this.routeMode() !== "schedule") return false;

    return _this.routeIsDay(position) ? !_this.franjaVisible.day : !_this.franjaVisible.night;
  };
```

- [ ] **Step 2: Refactorizar la rama `schedule` de `routeColorAt`** para usar el helper (elimina la lógica duplicada). El bloque actual (líneas ~459-472):

```js
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
```

queda:

```js
    if (mode === "schedule") {
      var s = _this.routeSchedule();

      return _this.routeIsDay(position) ? s.day_color : s.night_color;
    }
```

- [ ] **Step 3: Reset de toggles en cada búsqueda nueva.** En `_this.get = function () {` (línea ~138), insertar como PRIMERA línea del cuerpo:

```js
    _this.franjaVisible = { day: true, night: true };
```

- [ ] **Step 4: Verificar.**

Run: `node --check resources/assets/js/controller/history.js` → exit 0.
Smoke (extraer el bloque de helpers a un script scratch con stubs `window`/`$`/`_this` — patrón extract-and-eval usado en la feature anterior; borrar tras usar):
- `routeIsDay({t:'2026-08-09 12:00:00'})` con schedule default → `true`; `'2026-08-09 03:00:00'` → `false`.
- Cross-midnight `{day_from:'20:00',day_to:'06:00'}`: `21:00` → `true`, `05:00` → `true`, `12:00` → `false`.
- `routeColorAt('schedule', {t:'... 12:00:00'})` → day_color (el refactor no cambió el comportamiento).
- `routeFranjaHidden`: modo `trips` → siempre `false`; modo `schedule` con `franjaVisible.night=false` y posición nocturna → `true`, diurna → `false`.

- [ ] **Step 5: Commit.**

```bash
git add resources/assets/js/controller/history.js
git commit -m "feat(history): clasificacion dia/noche compartida y estado de toggles de franja"
```

---

## Task 3: `history.js` — ocultar tramos en mapa, marcadores y flechas

**Files:**
- Modify: `resources/assets/js/controller/history.js`

- [ ] **Step 1: Cortar la polilínea en posiciones ocultas.** En el bucle de posiciones de `parse()`, el bloque actual (~563-573):

```js
          $.each(item.positions, function (pindex, position) {
            _this.positions.push(position);

            if (_this.skipInvalid && !position.v) {
              return;
            }

            let point = {
```

queda:

```js
          $.each(item.positions, function (pindex, position) {
            _this.positions.push(position);

            if (_this.skipInvalid && !position.v) {
              return;
            }

            if (_this.routeFranjaHidden(position)) {
              position._hidden = true;

              if (poly != null && poly.getLatLngs().length > 1) {
                polyArray.push(poly);
              }

              poly = null;
              lastColor = null;

              return;
            }

            position._hidden = false;

            let point = {
```

(Cerrar la polilínea en curso y anular `poly`/`lastColor` deja un HUECO real — la siguiente posición visible arranca polilínea nueva, sin línea puente.)

- [ ] **Step 2: Ocultar marcadores de paradas iniciadas en franja oculta.** El bloque actual (~601-604):

```js
        if (item.status == 2) {
          if (!app.settings.showHistoryStop) return;

          icon = _this.getParkingIcon(item);
```

queda:

```js
        if (item.status == 2) {
          if (!app.settings.showHistoryStop) return;

          if (item.positions && item.positions.length && _this.routeFranjaHidden(item.positions[0])) return;

          icon = _this.getParkingIcon(item);
```

- [ ] **Step 3: Saltar posiciones ocultas en las flechas de zoom alto.** En `polylinePointsSet` (~línea 333), el bloque:

```js
      if (_this.skipInvalid && !position.v) return;
```

queda:

```js
      if (_this.skipInvalid && !position.v) return;

      if (position._hidden) return;
```

- [ ] **Step 4: Verificar.**

Run: `node --check resources/assets/js/controller/history.js` → exit 0.
Smoke con harness (stubs de `L.polyline` que registran latlngs; replicar el bucle de parse con items sintéticos de 6 posiciones alternando franja): al ocultar noche, los segmentos resultantes NO conectan la última posición diurna con la primera diurna del tramo siguiente (hay 2+ polilíneas separadas). Borrar scratch tras usar.

- [ ] **Step 5: Commit.**

```bash
git add resources/assets/js/controller/history.js
git commit -m "feat(history): ocultar tramos, paradas y flechas de la franja desactivada"
```

---

## Task 4: Cards informativos + toggles (UI)

**Files:**
- Modify: `Tobuli/Views/Frontend/History/bottom.blade.php` (línea 1-2)
- Modify: `resources/assets/js/controller/history.js`

- [ ] **Step 1: Contenedor en el blade.** El inicio actual del archivo:

```blade
<div class="footer-table" id="bottom-history">
    <div class="bottom-history-header">
```

queda:

```blade
<div class="footer-table" id="bottom-history">
    <div id="history-stats"></div>
    <div class="bottom-history-header">
```

- [ ] **Step 2: Añadir `_this.statsRender`.** Insertar INMEDIATAMENTE ANTES de `  _this.parse = function () {` (tras el cierre de `_this.routeLegendRemove`, línea ~525):

```js

  _this.statsRender = function () {
    var $wrap = $("#history-stats");

    if (!$wrap.length) return;

    if (window.history_items == null) {
      $wrap.empty();
      return;
    }

    var moveMs = 0,
      stopMs = 0,
      maxSpeed = 0,
      distanceRaw = 0;

    $.each(window.history_items, function (index, item) {
      if (typeof item.positions === "undefined") return;
      if (item.status != 1 && item.status != 2) return;

      var prev = null;

      $.each(item.positions, function (pindex, position) {
        if (!_this.routeFranjaHidden(position)) {
          var spd = parseFloat(position.s) || 0;
          if (spd > maxSpeed) maxSpeed = spd;

          distanceRaw += parseFloat(position.d) || 0;
        }

        if (prev !== null && !_this.routeFranjaHidden(prev)) {
          var a = moment(prev.t, "YYYY-MM-DD HH:mm:ss");
          var b = moment(position.t, "YYYY-MM-DD HH:mm:ss");

          if (a.isValid() && b.isValid()) {
            var dt = b.diff(a);

            if (dt > 0) {
              if (item.status == 1) moveMs += dt;
              else stopMs += dt;
            }
          }
        }

        prev = position;
      });
    });

    var units = (app.settings && app.settings.units) || {};
    var distUnit = (units.distance && units.distance.unit) || "km";
    var distRatio = (units.distance && units.distance.radio) || 1;
    var speedUnit = (units.speed && units.speed.unit) || "";

    function fmtDur(ms) {
      var mins = Math.floor(ms / 60000);

      return Math.floor(mins / 60) + "h " + (mins % 60) + "m";
    }

    function card(value, label) {
      return (
        '<div style="flex:1;text-align:center;padding:4px 8px;background:#f8f9fa;border:1px solid #e3e6e8;border-radius:4px;margin:0 3px;">' +
        '<div style="font-size:16px;font-weight:bold;line-height:1.2;">' + value + "</div>" +
        '<div style="font-size:10px;color:#777;">' + label + "</div>" +
        "</div>"
      );
    }

    var html =
      '<div style="display:flex;align-items:stretch;padding:4px 6px;">' +
      card(fmtDur(moveMs), "En movimiento") +
      card(fmtDur(stopMs), "Detenido") +
      card(maxSpeed + " " + speedUnit, "Vel. máxima") +
      card((distanceRaw * distRatio).toFixed(1) + " " + distUnit, "Recorrido");

    if (_this.routeMode() === "schedule") {
      html +=
        '<div style="display:flex;flex-direction:column;justify-content:center;padding:0 8px;font-size:12px;white-space:nowrap;">' +
        '<label style="margin:0;font-weight:normal;cursor:pointer;"><input type="checkbox" id="franja-day"' +
        (_this.franjaVisible.day ? " checked" : "") +
        "> Día</label>" +
        '<label style="margin:0;font-weight:normal;cursor:pointer;"><input type="checkbox" id="franja-night"' +
        (_this.franjaVisible.night ? " checked" : "") +
        "> Noche</label>" +
        "</div>";
    }

    html += "</div>";

    $wrap.html(html);

    $wrap.find("#franja-day, #franja-night").on("change", function () {
      _this.franjaVisible.day = $wrap.find("#franja-day").prop("checked");
      _this.franjaVisible.night = $wrap.find("#franja-night").prop("checked");

      _this.parse();
    });
  };
```

(Notas de diseño ya decididas: `position.s` YA está en la unidad de la app — no convertir; `position.d` está en km crudos — multiplicar el TOTAL por `distRatio`; los deltas de tiempo se atribuyen por la franja de la posición INICIAL del intervalo; `$wrap.html()` destruye los checkboxes anteriores junto con sus listeners — no hay fugas ni dobles bindings.)

- [ ] **Step 3: Llamar a `statsRender`.** (a) En `parse()`, la línea `_this.routeLegendAdd();` (~661) queda:

```js
      _this.routeLegendAdd();

      _this.statsRender();
```

(b) En `clear()`, la línea `_this.routeLegendRemove();` (~291) queda:

```js
    _this.routeLegendRemove();

    $("#history-stats").empty();
```

- [ ] **Step 4: Verificar.**

Run: `node --check resources/assets/js/controller/history.js` → exit 0.
Blade compile check del bottom.blade.php (patrón: bootstrap app + `Blade::compileString` + `php -l` del compilado; script scratch, borrar tras usar) → sin errores.
Smoke del cálculo (harness con stubs jQuery mínimos + moment real desde `resources/assets/js/lib/moment.js`, items sintéticos: 1 drive de 3 posiciones diurnas con `d:1.5` c/u y `s` 40/80/60 + 1 stop de 2 posiciones nocturnas de 10 min): con todo visible → move=lo esperado, stop=10m, max=80, dist=4.5×ratio; con `franjaVisible.night=false` y modo schedule → stop=0h 0m. Borrar scratch.

- [ ] **Step 5: Commit.**

```bash
git add Tobuli/Views/Frontend/History/bottom.blade.php resources/assets/js/controller/history.js
git commit -m "feat(history): cards informativos y toggles dia/noche sobre el grafico"
```

---

## Task 5: Recompilar el bundle

**Files:**
- Modify (generado): `public/assets/js/app.js`

- [ ] **Step 1: Regenerar por concat.** NO usar npm/gulp. Escribir en el scratchpad el script de concat (mismo de commit `be082f9`: lista literal `scripts.app` del `gulpfile.js` líneas 74-118 — 39 archivos, empieza en `lib/moment.js`, termina en `controller/dashboard.js` — leídos utf8, unidos con `'\n'`, escritos a `public/assets/js/app.js`) y ejecutarlo con `node`. Si el script `build_app_bundle.js` aún existe en el scratchpad de la sesión, reutilizarlo.

- [ ] **Step 2: Verificar.**

- `node --check public/assets/js/app.js` → exit 0.
- Greps en el bundle: `statsRender`, `routeIsDay`, `franjaVisible`, `history-stats` → ≥1 match cada uno; `function History()` exactamente 1.
- `git status --short public/assets/js/app.js` → modificado.

- [ ] **Step 3: Commit.**

```bash
git add public/assets/js/app.js
git commit -m "build: recompilar bundle app.js (toggles dia/noche + cards)"
```

---

## Task 6: Verificación end-to-end (criterios del spec)

Manual, en el navegador, con el vehículo en modo horario que ya probaste:

- [ ] 1. Cargar historial → aparecen los 4 cards entre mapa y gráfico + toggles `Día`/`Noche`; valores plausibles (compáralos con el popup del marcador START/END).
- [ ] 2. Desmarcar `Noche` → desaparecen los tramos negros SIN líneas puente entre huecos, sus flechas y las paradas nocturnas; el mapa re-encuadra; los cards bajan a solo lo diurno.
- [ ] 3. Desmarcar ambos → mapa sin ruta, cards `0h 0m` / `0` / `0.0`, sin errores de consola.
- [ ] 4. Cambiar device/fechas y "Mostrar historial" → toggles vuelven a ambos activos.
- [ ] 5. Vehículo en modo viajes/único/velocidad → cards con totales, SIN toggles.
- [ ] 6. Limpiar historial / cambiar de pestaña → cards desaparecen.
- [ ] 7. Suite PHP verde: `vendor\bin\phpunit tests\Unit\DeviceRouteConfigTest.php`.
