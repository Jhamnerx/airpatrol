# Diseño: "Colores del recorrido — Historial" por vehículo (estilo Wialon)

**Fecha:** 2026-08-08
**Estado:** aprobado por el usuario (arquitectura frontend, modo horario con rango simple, default `trips`)

## Objetivo

Configuración POR VEHÍCULO que controla el color de la polilínea del historial, con 5 modos:

| Modo | `route_color_type` | Comportamiento |
|---|---|---|
| Por viajes (default) | `trips` | Cada viaje (tramo drive) un color de la paleta, paradas y huecos en gris |
| Único | `single` | Toda la ruta de un solo color elegido |
| Por velocidad | `speed` | Color por rangos de velocidad configurables |
| Por sensor | `sensor` | **Placeholder deshabilitado** (reservado) |
| Por horario | `schedule` | Color diurno / nocturno según la hora de cada posición |

La UI vive en el tab "Avanzado" del modal de edición del dispositivo; el visor de
historial la lee y pinta la ruta según el modo.

## Decisiones de arquitectura (con hallazgos que las sustentan)

1. **El color se calcula en el FRONTEND** (`resources/assets/js/controller/history.js`).
   El mecanismo de dibujo ya es segmentado: `parse()` (líneas ~397-424) crea una
   `L.polyline` nueva cada vez que cambia `position.c`. No se toca el pipeline backend
   de History ni los plugins existentes (`AppendRouteColor`, `business_private_drive`,
   `route_color`); el color backend (`#0000FF`) queda como fallback si falta config.
2. Los datos que recibe el JS ya están listos para los modos:
   - `position.s` = velocidad **ya convertida a la unidad de la app** (kph/mph/kn) —
     los rangos del editor se comparan contra este valor, sin conversión.
   - `position.t` = fecha/hora **ya convertida a la zona horaria del usuario**
     (`"Y-m-d H:i:s"`) — el modo horario compara `HH:MM` directamente.
   - Los items de `window.history_items` ya vienen agrupados: `status:1` = drive,
     `status:2` = stop, `3/4` = start/end (sin `positions`), `5` = event.
3. **Modo horario propio por vehículo** (rango simple día/noche), NO se reutiliza el
   plugin global `business_private_drive` (es por instalación, no por vehículo) ni la
   rejilla semanal de `ScheduleService` (UI pesada para el modal; YAGNI).
4. **Default `trips` cambia el aspecto actual**: vehículos sin configurar pasan de azul
   sólido a un color por viaje + gris en paradas (estilo Wialon). Decidido y aceptado.

## 1) Base de datos

Migración `add_route_color_to_devices` (estilo del proyecto, ver
`database/migrations/2026_06_23_000000_add_jimi_model_to_devices.php`), columnas en
`devices`:

| Columna | Tipo | Default | Uso |
|---|---|---|---|
| `route_color_type` | VARCHAR(10) NOT NULL | `'trips'` | modo activo |
| `route_color` | VARCHAR(10) NULL | — | hex del modo "único" |
| `route_speed_ranges` | TEXT NULL | — | JSON `[{from,to,color},…]` |
| `route_sensor_id` | INT NULL | — | reservado (modo sensor) |
| `route_schedule` | TEXT NULL | — | JSON `{day_from,day_to,day_color,night_color}` |

- `down()` revierte las 5 columnas. Idempotencia con `Schema::hasColumn()` (patrón
  `add_max_speed_to_devices_table`).
- Añadir los 5 campos a `Device::$fillable` (`Tobuli/Entities/Device.php:104-154`).
- Validación en `Tobuli/Validation/DeviceFormValidator.php`, en `create` **y** `update`:
  - `route_color_type` → `in:trips,single,speed,sensor,schedule` (nullable)
  - `route_color` → `nullable|regex:/^#[0-9a-fA-F]{6}$/`
  - `route_speed_ranges`, `route_schedule` → `nullable|string`
- **Sin permisos por campo** (`device.route_*` NO se añade a `config/permissions.php`):
  `onlyEditables()` deja pasar campos no listados, así el flujo estándar de
  `DeviceModalHelper::edit()` → `DeviceService::update()` los persiste por
  mass-assignment sin lógica extra. Se puede gatear con permisos más adelante si hace
  falta.
- Sin cambios en `DeviceService::normalize()` ni `getDefaults()` (el default de DB cubre
  los devices nuevos).

### Defaults canónicos (compartidos editor ↔ visor)

Rangos de velocidad (también cuando el campo está vacío o el JSON es corrupto):

```json
[{"from":0,"to":40,"color":"#22d3ee"},{"from":40,"to":60,"color":"#3b82f6"},
 {"from":60,"to":90,"color":"#6366f1"},{"from":90,"to":120,"color":"#a855f7"},
 {"from":120,"to":null,"color":"#ef4444"}]
```

Color "único" por defecto: `#2563eb`.

Horario por defecto (también ante JSON corrupto):

```json
{"day_from":"06:00","day_to":"18:00","day_color":"#2563eb","night_color":"#000000"}
```

Paleta de viajes (cicla por orden de viaje):
`#2563eb #16a34a #0891b2 #7c3aed #db2777 #ea580c #ca8a04 #0d9488 #9333ea #dc2626`
— paradas y huecos en gris `#94a3b8`.

## 2) UI del editor

Partial: `Tobuli/Views/Frontend/Devices/partials/advanced.blade.php` — bloque
"Colores del recorrido — Historial:" al final del partial (tras `timezone_id`),
con 5 filas de radio `route_color_type`:

1. **Por viajes** (`value="trips"`) — radio solo, default.
2. **Único** (`value="single"`) — radio + `<input type="color" name="route_color">`.
3. **Por velocidad** (`value="speed"`) — radio + barra de preview con gradiente por
   tramos + botón `+` (añadir rango) + botón `↺` (restaurar defaults). Debajo, filas
   de rangos editables. Hidden `<input name="route_speed_ranges">` con el JSON.
4. **Por sensor** (`value="sensor"`) — radio SIEMPRE `disabled` + nota
   "(requiere un sensor con rangos de colores por intervalo)". Placeholder.
5. **Por horario** (`value="schedule"`) — radio + "Día de `[time]` a `[time]`" +
   color de día + color de noche. Hidden `<input name="route_schedule">` con el JSON
   serializado por JS. Noche = complemento del rango de día; soporta rangos que cruzan
   medianoche (`day_from > day_to`).

Lógica JS (IIFE jQuery inline en el partial — el modal se inyecta por AJAX, el script
se autoejecuta con el partial):

- `normalize()`: ordenar rangos por `from`; encadenarlos contiguos desde 0 (`from` de
  cada uno = `to` del anterior; el primero siempre 0); el último siempre `to = null`
  (mostrar ∞). Si un `to` es NaN o ≤ `from`, forzarlo a `from + 10`.
- Render de filas: `from` en input deshabilitado, `to` editable (última fila "∞"),
  `<input type="color">`, botón `×` de borrar solo si hay más de 1 rango.
- Barra preview proporcional: escala = `from` del último rango (o 100 si solo hay uno);
  el segmento ∞ ocupa 18% fijo; los demás `max(6, ((to-from)/escala)*82)`%.
- Botón `+`: insertar ANTES del último rango
  `{from:<from del último>, to:<from del último>+20, color:'#94a3b8'}`.
- Editar cualquier `to`/color, borrar o añadir un rango AUTO-SELECCIONA el radio
  "Por velocidad". Editar horas/colores del horario AUTO-SELECCIONA "Por horario".
- `↺`: restaurar los 5 defaults de velocidad.
- Tras cada cambio: `normalize()` + re-render + `JSON.stringify` al hidden
  correspondiente (`route_speed_ranges` / `route_schedule`).
- Tema legacy: los `.radio` de Bootstrap 3 se estilizan con el input absoluto cubriendo
  el contenedor → el radio+label van solos en su div y los controles FUERA, en la misma
  fila flex, para que sigan siendo clicables.
- Valores iniciales: desde `$item->route_*` (Blade los inyecta en el hidden/inputs);
  JSON corrupto o vacío → defaults.

## 3) Puente PHP → JS

- `ModalHelpers/HistoryModalHelper.php::get()` añade al array de retorno (~línea 286):

```php
'route' => [
    'type'     => $device->route_color_type ?: 'trips',
    'color'    => $device->route_color,
    'ranges'   => json_decode($device->route_speed_ranges) ?: null,
    'schedule' => json_decode($device->route_schedule) ?: null,
],
```

- `Tobuli/Views/Frontend/History/index.blade.php` (líneas ~48-59) emite
  `window.history_route = {!!json_encode($route)!!};` junto a `history_items`; la rama
  `@else` lo pone a `null`.
- Mapeo `type:'sensor'` → el front lo trata como `'speed'` (fallback mientras el modo
  sensor no exista).

## 4) Visor de historial (`resources/assets/js/controller/history.js`)

En `parse()`, antes del bucle de posiciones existente, se resuelve `colorAt(item, position)`
y se asigna a `position.c` — el corte de polilíneas por cambio de color, las flechas
(`polylineDecorator` + flechas por posición), el `fitBounds` y los popups existentes
siguen funcionando sin cambios:

- `single` → siempre `route.color || '#2563eb'`.
- `speed` (y `sensor`) → primer rango con `to == null` o `parseFloat(position.s) < to`;
  rangos inválidos/corruptos → defaults canónicos.
- `trips` (y `route` null/ausente) → contador de viajes que incrementa por cada item
  `status:1`; color = `paleta[viaje % 10]`; items `status:2` (stop) en gris `#94a3b8`.
- `schedule` → extraer `HH:MM` de `position.t`; día si está dentro de
  `[day_from, day_to)` (con vuelta por medianoche si `day_from > day_to`); si no, noche.
  JSON corrupto → defaults.
- La selección de tramo (resaltado `#66FF33`), `skipInvalid` y el resto de `parse()`
  no cambian.

### Leyenda (solo modo `speed`)

Control Leaflet propio (esquina inferior) visible únicamente cuando el modo efectivo es
`speed`: barra de tramos + ticks con los `from` de cada rango y "∞" al final, generada
desde los rangos del vehículo con la misma fórmula proporcional del editor. Se crea en
`parse()` y se elimina en `clear()`.

## 5) Build

`history.js` va solo en el bundle `app`: recompilar con `npx gulp script-app`
(o `npx gulp scripts` para los 3 bundles). No hay script npm; no existe flag
`--production` en este gulpfile.

## 6) Fuera de alcance

- Lógica real del modo sensor (solo columna reservada + radio deshabilitado).
- Colores en la API móvil / ClientLite (`getApi()` usa otra estructura), reportes y
  exports — siguen con el color backend actual.
- Permisos por campo `device.route_*`.

## 7) Criterios de aceptación

1. Editar un device, elegir "Único" con un color, guardar, reabrir el modal → persiste.
2. "Por velocidad": añadir/editar/borrar rangos mantiene rangos contiguos desde 0 con
   último ∞; guardar y recargar el historial pinta la ruta con esos colores por tramos,
   comparando contra la velocidad en la unidad que muestra la app.
3. "Por viajes" (default): cada viaje un color distinto de la paleta, paradas en gris.
4. "Por horario": con defaults, posiciones entre 06:00 y 18:00 (hora del usuario) en
   azul `#2563eb`, el resto en negro `#000000`; rango cruzando medianoche funciona;
   guardar y reabrir el modal persiste horas y colores.
5. Un device sin configurar (columnas en default/null) pinta "por viajes" sin errores;
   JSON corrupto en `route_speed_ranges` o `route_schedule` → defaults.
6. "Por sensor" visible pero deshabilitado, no seleccionable.
7. Leyenda visible en el mapa solo en modo `speed`, desaparece al limpiar el historial.
8. Bundle `app.js` recompilado con Gulp.
