# Jimi Video API — Documentación para React Native

Endpoints para acceder a video en vivo e histórico de dispositivos Jimi IoT.

---

## Autenticación

Todos los endpoints requieren el hash del usuario como query parameter o header.

| Método          | Ejemplo                 |
| --------------- | ----------------------- |
| Query parameter | `?user_api_hash={hash}` |
| HTTP Header     | `user-api-hash: {hash}` |

El `user_api_hash` se obtiene al hacer login:

```http
POST /login
Content-Type: application/json

{
  "email": "usuario@ejemplo.com",
  "password": "contraseña"
}
```

```json
{
  "status": 1,
  "user_api_hash": "abc123def456..."
}
```

---

## Base URL

```
https://tu-dominio.com/
```

---

## 1. Video en Vivo

### `GET /jimi/devices/{id}/live`

Obtiene la URL de streaming en vivo de un dispositivo Jimi.

**Path params**

| Param | Tipo    | Descripción        |
| ----- | ------- | ------------------ |
| `id`  | integer | ID del dispositivo |

**Ejemplo de solicitud**

```http
GET /jimi/devices/42/live?user_api_hash=abc123def456
```

**Respuesta 200 — OK**

```json
{
  "url": "https://open.tracksolidpro.com/play.html?token=xxx",
  "imei": "865478070000239",
  "device_id": 42,
  "device_name": "Unidad 01"
}
```

> La `url` devuelta puede ser una página web embebible o una URL de stream directo dependiendo del modelo del dispositivo.

**Errores posibles**

| Código | Descripción                                                |
| ------ | ---------------------------------------------------------- |
| 401    | `user_api_hash` inválido o faltante                        |
| 404    | Dispositivo no encontrado o no pertenece al usuario        |
| 422    | El dispositivo no tiene video Jimi, o Jimi no devolvió URL |
| 500    | Error interno                                              |

---

## 2. Video Histórico

El video histórico requiere 3 pasos secuenciales. El cliente (React Native) debe conservar los valores `instruction_id` y `app_id` entre llamadas.

```
PASO 1 → /history/cmd      ← Envía comando al dispositivo
PASO 2 → /history/list     ← Polling hasta tener segmentos (repetir si pending=true)
PASO 3 → /history/stream   ← Obtiene URL WebSocket del segmento a reproducir
         /history/close    ← Cierra el stream al terminar
```

---

### Paso 1 — Enviar Comando

#### `POST /jimi/devices/{id}/history/cmd`

Ordena al dispositivo que prepare y suba su lista de archivos de video para la fecha indicada.

**Path params**

| Param | Tipo    | Descripción        |
| ----- | ------- | ------------------ |
| `id`  | integer | ID del dispositivo |

**Body (JSON o form-data)**

| Campo     | Tipo    | Requerido | Descripción                                          |
| --------- | ------- | --------- | ---------------------------------------------------- |
| `channel` | integer | Sí        | Canal de cámara. JT808: desde `1`. Concox: desde `0` |
| `date`    | string  | Sí        | Fecha en formato `Y-m-d` (ej: `2026-04-27`)          |

**Ejemplo de solicitud**

```http
POST /jimi/devices/42/history/cmd?user_api_hash=abc123def456
Content-Type: application/json

{
  "channel": 1,
  "date": "2026-04-27"
}
```

**Respuesta 200 — OK**

```json
{
  "instruction_id": "550e8400-e29b-41d4-a716-446655440000",
  "app_id": "aB3xK9pLmQr7t",
  "poll_interval_ms": 1000,
  "max_polls": 20
}
```

> Guardar `instruction_id` y `app_id` — se necesitan en los pasos siguientes.

---

### Paso 2 — Obtener Lista de Segmentos (Polling)

#### `POST /jimi/devices/{id}/history/list`

Consulta si el dispositivo ya respondió con su lista de segmentos de video. Debe repetirse cada `poll_interval_ms` hasta que `pending` sea `false` o se alcance `max_polls`.

**Body (JSON o form-data)**

| Campo            | Tipo   | Requerido | Descripción                |
| ---------------- | ------ | --------- | -------------------------- |
| `instruction_id` | string | Sí        | UUID obtenido en el Paso 1 |

**Ejemplo de solicitud**

```http
POST /jimi/devices/42/history/list?user_api_hash=abc123def456
Content-Type: application/json

{
  "instruction_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Respuesta 200 — Lista disponible**

```json
{
  "pending": false,
  "segments": [
    {
      "channel": "1",
      "begin_time": "2026-04-27 10:00:00",
      "end_time": "2026-04-27 10:03:12",
      "file_name": null,
      "file_size": 8325778,
      "sort_begin": "2026-04-27 10:00:00"
    },
    {
      "channel": "1",
      "begin_time": "2026-04-27 10:05:00",
      "end_time": "2026-04-27 10:08:44",
      "file_name": null,
      "file_size": 9102345,
      "sort_begin": "2026-04-27 10:05:00"
    }
  ]
}
```

> - `file_name` es `null` en dispositivos JT808/1078 (JC450, JC451, JC371, JC181, JC182). Se usa `begin_time`/`end_time`.
> - `file_name` tiene valor en dispositivos Concox (JC261, JC400). Se usa en el Paso 3 como `file_name_list`.
> - Todos los tiempos están en **UTC**.

**Respuesta 200 — Aún no disponible (repetir)**

```json
{
  "pending": true
}
```

**Lógica de polling recomendada**

```js
let attempts = 0;
const MAX = 20;
const INTERVAL = 1000; // ms

const pollList = async () => {
  if (attempts >= MAX) {
    // Timeout: el dispositivo no respondió
    return;
  }
  const res = await fetch(
    `/jimi/devices/${id}/history/list?user_api_hash=${hash}`,
    {
      method: "POST",
      body: JSON.stringify({ instruction_id: instructionId }),
    },
  );
  const data = await res.json();
  if (data.pending) {
    attempts++;
    setTimeout(pollList, INTERVAL);
  } else {
    // Renderizar data.segments
  }
};
pollList();
```

---

### Paso 3 — Obtener URL de Stream del Segmento

#### `POST /jimi/devices/{id}/history/stream`

Solicita a Jimi la URL WebSocket FLV para reproducir un segmento específico.

**Body — Dispositivos JT808/1078** (JC450, JC451, JC371, JC181, JC182)

| Campo        | Tipo    | Requerido | Descripción                                   |
| ------------ | ------- | --------- | --------------------------------------------- |
| `channel`    | integer | Sí        | Canal de cámara (desde `1`)                   |
| `app_id`     | string  | Sí        | Obtenido en el Paso 1                         |
| `begin_time` | string  | Sí        | `begin_time` del segmento (`Y-m-d H:i:s` UTC) |
| `end_time`   | string  | Sí        | `end_time` del segmento (`Y-m-d H:i:s` UTC)   |

**Body — Dispositivos Concox** (JC261, JC400)

| Campo            | Tipo    | Requerido | Descripción                 |
| ---------------- | ------- | --------- | --------------------------- |
| `channel`        | integer | Sí        | Canal de cámara (desde `0`) |
| `app_id`         | string  | Sí        | Obtenido en el Paso 1       |
| `file_name_list` | string  | Sí        | `file_name` del segmento    |

**Ejemplo de solicitud (JT808)**

```http
POST /jimi/devices/42/history/stream?user_api_hash=abc123def456
Content-Type: application/json

{
  "channel": 1,
  "app_id": "aB3xK9pLmQr7t",
  "begin_time": "2026-04-27 10:00:00",
  "end_time": "2026-04-27 10:03:12"
}
```

**Ejemplo de solicitud (Concox)**

```http
POST /jimi/devices/42/history/stream?user_api_hash=abc123def456
Content-Type: application/json

{
  "channel": 0,
  "app_id": "aB3xK9pLmQr7t",
  "file_name_list": "2026_04_27_10_00_00_01.mp4"
}
```

**Respuesta 200 — OK**

```json
{
  "url": "ws://113.108.62.203:11014/1/865478070000239.history.flv?secret=xyz789",
  "app_id": "aB3xK9pLmQr7t"
}
```

> La URL `ws://` es un stream FLV sobre WebSocket. Requiere una librería compatible como:
>
> - **React Native**: `react-native-vlc-media-player`, `react-native-video` con soporte RTMP, o un WebView con `flv.js`.
> - El player nativo de React Native (`<Video>` de `expo-av`) **no soporta** FLV/WebSocket directamente.

---

### Cerrar Stream

#### `POST /jimi/devices/{id}/history/close`

Libera los recursos del stream en Jimi. **Debe llamarse siempre** al salir de la pantalla de video o al cambiar de segmento.

**Body (JSON o form-data)**

| Campo     | Tipo    | Requerido | Descripción                                  |
| --------- | ------- | --------- | -------------------------------------------- |
| `channel` | integer | Sí        | Canal de cámara                              |
| `app_id`  | string  | Sí        | Obtenido en el Paso 1                        |
| `type`    | string  | No        | `"0"` = en vivo, `"1"` = histórico (default) |

**Ejemplo de solicitud**

```http
POST /jimi/devices/42/history/close?user_api_hash=abc123def456
Content-Type: application/json

{
  "channel": 1,
  "app_id": "aB3xK9pLmQr7t",
  "type": "1"
}
```

**Respuesta 200 — OK**

```json
{
  "success": true
}
```

---

## Resumen de Endpoints

| Método | Ruta                                | Descripción                           |
| ------ | ----------------------------------- | ------------------------------------- |
| GET    | `/jimi/devices/{id}/live`           | URL de video en vivo                  |
| POST   | `/jimi/devices/{id}/history/cmd`    | Paso 1: Enviar comando al dispositivo |
| POST   | `/jimi/devices/{id}/history/list`   | Paso 2: Polling de segmentos          |
| POST   | `/jimi/devices/{id}/history/stream` | Paso 3: URL WebSocket del segmento    |
| POST   | `/jimi/devices/{id}/history/close`  | Cerrar stream activo                  |

---

## Notas de Compatibilidad por Modelo

| Modelo       | Protocolo  | Canales desde | Param tiempo            |
| ------------ | ---------- | ------------- | ----------------------- |
| JC450, JC451 | JT808/1078 | `1`           | `begin_time`/`end_time` |
| JC371        | JT808/1078 | `1`           | `begin_time`/`end_time` |
| JC181, JC182 | JT808/1078 | `1`           | `begin_time`/`end_time` |
| JC261, JC400 | Concox     | `0`           | `file_name_list`        |

---

## Errores Comunes

| Código | Mensaje                                          | Causa                                       |
| ------ | ------------------------------------------------ | ------------------------------------------- |
| 401    | `login_failed`                                   | `user_api_hash` inválido o expirado         |
| 404    | `Dispositivo no encontrado.`                     | El `id` no existe o no pertenece al usuario |
| 422    | `El campo "date" es requerido en formato Y-m-d.` | `date` faltante o formato incorrecto        |
| 422    | `El campo "instruction_id" es requerido.`        | Falta `instruction_id` en el Paso 2         |
| 422    | Mensaje de error de Jimi                         | Error devuelto por el servidor Jimi IoT     |
| 500    | `Error interno del servidor.`                    | Error inesperado en el servidor             |
