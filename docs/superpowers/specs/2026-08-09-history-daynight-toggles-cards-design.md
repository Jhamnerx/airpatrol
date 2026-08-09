# Diseño: Toggles Día/Noche + Cards informativos del historial

**Fecha:** 2026-08-09
**Estado:** aprobado por el usuario (misma rama `feature/route-colors-history`; toggles solo
en modo horario; cards reactivos a lo visible; ubicados encima del gráfico inferior)
**Extiende:** `2026-08-08-route-colors-history-design.md` (ya implementado en esta rama)

## Objetivo

1. **Toggles Día / Noche**: cuando el vehículo está en modo "Por horario", poder ocultar
   o mostrar los tramos diurnos y nocturnos del recorrido en el mapa, para analizar cada
   franja por separado.
2. **Cards informativos**: fila de 4 cards entre el mapa y el gráfico inferior con
   **Horas en movimiento**, **Horas detenido**, **Velocidad máxima** y **Km recorridos**,
   calculados sobre lo actualmente VISIBLE (los toggles filtran; sin toggles = totales).

## Decisiones de arquitectura

1. **Todo se calcula en el frontend** reutilizando el pipeline existente. Los datos por
   posición ya llegan al JS (`t` hora usuario, `s` velocidad en unidad de la app); solo
   falta la distancia por posición.
2. **Única línea de backend nueva**: en `ModalHelpers/HistoryModalHelper.php::get()`
   (payload de posiciones, ~línea 216-225) añadir `'d' => (float) $position->distance`.
   `AppendDistance` ya la calcula para el visor web (delta en KM crudos respecto de la
   posición anterior); el frontend la convierte una sola vez con
   `app.settings.units.distance.radio`/`unit` (ya expuestos en `getJsConfig()`,
   `Tobuli/Helpers/Helper.php:1608-1611`).
3. **Toggle = re-parse**: el estado de los toggles vive en `app.history`
   (`_this.franjaVisible = {day: true, night: true}`) y persiste entre parses. Al cambiar
   un toggle se re-ejecuta `_this.parse()` (ya es determinista). El re-encuadre
   (`fitBounds`) a lo visible es comportamiento aceptado.
4. **Sin migraciones, sin cambios del modal de device, sin CSS compilado** (no hay
   toolchain sass en la máquina): estilos inline, como la leyenda de velocidad.
5. `moment.js` ya está en el bundle `app` — se usa para parsear `position.t`
   (`'YYYY-MM-DD HH:mm:ss'`) y calcular deltas de tiempo.

## 1) Clasificación día/noche y ocultamiento en el mapa

- Helper nuevo en `history.js`: `_this.routeIsDay(position)` → aplica al `HH:MM` de
  `position.t` la misma lógica de `[day_from, day_to)` con vuelta por medianoche que
  `routeColorAt` usa hoy (extraer esa lógica compartida a una función para no duplicarla).
- Helper `_this.routeFranjaHidden(position)` → `true` si el modo efectivo es `schedule`
  Y la franja de la posición está desactivada en `_this.franjaVisible`. En cualquier
  otro modo devuelve siempre `false` (los toggles no existen fuera de `schedule`).
- En el bucle de posiciones de `parse()`: si `routeFranjaHidden(position)`, marcar
  `position._hidden = true`, CERRAR la polilínea en curso (push a `polyArray` y
  `poly = null`) y saltar la posición — el hueco queda vacío, NO se puentea con una
  línea recta. Si visible, `position._hidden = false`.
- Marcadores de parada (items status 2): ocultar el marcador si la hora de INICIO de la
  parada cae en franja oculta.
- Flechas por posición (`polylinePointsSet`): saltar posiciones con `_hidden` (mismo
  patrón que `skipInvalid`). El decorator de zoom bajo se alimenta de `polyArray`, así
  que sigue solo a lo visible automáticamente.
- Fuera de alcance (no reaccionan a los toggles): el gráfico inferior, el player, la
  lista de tramos del panel izquierdo y la tabla datalog.

## 2) Toggles (UI)

- Viven en la fila de cards (extremo derecho): `☑ Día` `☑ Noche` (checkboxes con label,
  ambos activos por defecto en cada carga de historial nueva; el estado persiste entre
  re-parses de la misma sesión de historial y se resetea en `get()`).
- Solo se renderizan cuando el modo efectivo es `schedule` (misma resolución de modo que
  `routeMode()`, incluye `sensor`→`speed`, o sea sensor NO muestra toggles).
- Cambiar un toggle: actualizar `_this.franjaVisible` y llamar `_this.parse()`.
- Ambos toggles desactivados = mapa sin ruta y cards en cero; permitido, no es error.

## 3) Cards informativos

- Contenedor `<div id="history-stats"></div>` como PRIMER hijo de `#bottom-history`
  en `Tobuli/Views/Frontend/History/bottom.blade.php` (antes de
  `.bottom-history-header`). El contenido lo genera JS (estilos inline).
- `_this.statsRender()` se llama al final de `parse()` (rama con datos) y en `clear()`
  (vacía el contenedor). Calcula iterando `window.history_items`:
  - **Horas en movimiento**: suma de deltas `t[i+1] - t[i]` entre posiciones consecutivas
    del MISMO item con `status == 1` (drive), contando solo intervalos cuya posición
    inicial es visible.
  - **Horas detenido**: ídem con items `status == 2` (stop).
  - **Velocidad máxima**: `max(position.s)` sobre posiciones visibles, mostrada con
    `app.settings.units.speed.unit`.
  - **Km recorridos**: suma de `position.d` de posiciones visibles ×
    `app.settings.units.distance.radio`, con 1 decimal y `app.settings.units.distance.unit`.
  - Formato de duraciones: `"Xh Ym"` (ej. `8h 24m`; `0h 0m` si nada visible).
- Los 4 cards en fila flex: valor grande + label pequeño debajo, estilos inline
  discretos (fondo claro, borde redondeado), etiquetas en español hardcodeadas:
  `En movimiento`, `Detenido`, `Vel. máxima`, `Recorrido`.
- Con ambos toggles activos (o modo ≠ schedule) los valores ≈ los totales que el
  backend ya muestra en los popups START/END; se acepta una diferencia de redondeo
  pequeña (los intervalos entre items no se cuentan). Es información de vistazo, la
  fuente contable siguen siendo los reportes.
- Robustez: posiciones sin `d` (payloads antiguos o `null`) cuentan como 0; `t`
  no parseable → el intervalo se descarta; nunca lanzar excepciones (cards en cero
  antes que romper el historial).

## 4) Build

Recompilar `public/assets/js/app.js` con el script de concat establecido en la rama
(commit `be082f9`); no hay gulp/npm funcional en la máquina.

## 5) Criterios de aceptación

1. Vehículo en modo horario: aparecen los toggles `Día`/`Noche` junto a los cards;
   desmarcar `Noche` elimina del mapa los tramos nocturnos (sin líneas puente), oculta
   sus flechas y los marcadores de paradas iniciadas de noche, re-encuadra, y los 4
   cards pasan a mostrar solo datos diurnos.
2. Vehículo en cualquier otro modo: no hay toggles, los cards muestran los totales del
   periodo consultado.
3. Los cards aparecen entre el mapa y el gráfico al cargar historial y se vacían al
   limpiar/cambiar de pestaña.
4. Km del card (todo visible) coincide razonablemente con la distancia del popup
   START/END del backend (misma unidad del usuario).
5. Ambos toggles apagados → mapa sin ruta, cards en `0h 0m` / `0` / `0.0`, sin errores
   de consola.
6. Historial de un device sin `d` en posiciones (no aplica tras el deploy, defensivo):
   card de Km muestra `0.0`, resto funciona.
7. Bundle recompilado.
