# AirPatrol — Contexto del Proyecto para GitHub Copilot

Este archivo se carga automáticamente en cada sesión de Copilot para este workspace. Proporciona el contexto necesario para asistir con el desarrollo.

---

## Descripción General

**AirPatrol** es un sistema SaaS de rastreo GPS en tiempo real. El producto comercial se llama **SEO GPS** (versión `3.7.11`, build `202503200000000000`).

- **Framework**: Laravel 8.x (PHP)
- **Entorno local**: Laragon en `c:\laragon2\www\airpatrol`
- **Base de datos principal**: MySQL
- **Cache/Colas**: Redis (Predis)
- **Cache geocoder**: SQLite
- **Tiempo real**: Socket.io (`socket/socket.js`)
- **Auth**: Laravel Passport (OAuth2)

---

## Arquitectura y Namespaces

El proyecto usa una estructura **no estándar** de Laravel con namespaces propios:

| Namespace        | Ruta            | Descripción                                                 |
| ---------------- | --------------- | ----------------------------------------------------------- |
| `App\`           | `app/`          | Controladores, Jobs, Events, Listeners, Middleware          |
| `Tobuli\`        | `Tobuli/`       | Core del dominio: Entities, Repositories, Services, Helpers |
| `ModalHelpers\`  | `ModalHelpers/` | Lógica de negocio para modales/formularios                  |
| `CustomFacades\` | `Facades/`      | Facades personalizadas (Appearance, Field, Language, etc.)  |

### Capas principales

```
app/Http/Controllers/
    Admin/          ← Panel de administración
    Api/            ← Controladores de API pública
    Auth/           ← Azure AD SSO
    Frontend/       ← Interfaz de usuario principal

Tobuli/
    Entities/       ← Modelos Eloquent (Device, User, Geofence, Alert…)
    Repositories/   ← Capa de acceso a datos
    Services/       ← Servicios de negocio
    Helpers/        ← Funciones globales (autoload en composer.json)
    Sensors/        ← Lógica de sensores GPS
    Reports/        ← Generación de reportes
    Exporters/      ← Exportación (Excel, PDF)
    Importers/      ← Importación de datos
    Protocols/      ← Protocolos de rastreo GPS

ModalHelpers/       ← Helpers para cada módulo de modal
```

---

## Entidades Principales (Tobuli/Entities/)

| Entidad                        | Descripción                                 |
| ------------------------------ | ------------------------------------------- |
| `Device`                       | Dispositivo GPS rastreado                   |
| `User`                         | Usuario del sistema (multi-tenant)          |
| `Geofence`                     | Zona geográfica virtual                     |
| `Alert`                        | Configuración de alertas                    |
| `Task` / `TaskSet`             | Gestión de tareas/órdenes                   |
| `Route`                        | Rutas predefinidas                          |
| `Poi`                          | Puntos de interés                           |
| `Report` / `ReportLog`         | Reportes generados                          |
| `DeviceSensor`                 | Sensores del dispositivo (fuel, temp, etc.) |
| `Schedule`                     | Programación de reportes/comandos           |
| `Checklist`                    | Listas de verificación                      |
| `Chat` / `ChatMessage`         | Mensajería interna                          |
| `BillingPlan` / `Subscription` | Planes y suscripciones                      |
| `Forward`                      | Reenvío de posiciones a terceros            |
| `EventLog`                     | Log de eventos del sistema                  |

---

## Funcionalidades Clave

- **Rastreo GPS en tiempo real** — posiciones, tail, estado (moving/stopped/offline)
- **Geofencing** — alertas al entrar/salir de zonas
- **Alertas y notificaciones** — Email (SendGrid), SMS (Plivo), Push (FCM/Firebase)
- **Reportes y exportación** — Excel (Maatwebsite), PDF (DomPDF / Snappy wkhtmltopdf)
- **Gestión de tareas** — asignación, estados, firmas digitales, campos personalizados
- **Multi-tenant** — usuarios, sub-usuarios, planes de facturación
- **Pagos** — Stripe, Braintree, Webtopay, Kevin
- **SSO Azure AD** — integración OAuth2 con Microsoft
- **API REST** — autenticada con Passport (Bearer token)
- **Socket.io** — actualizaciones en tiempo real al frontend
- **Cámaras/Media** — fotos de dispositivos, conversión de video (php-ffmpeg)
- **Checklists** — plantillas y registros de inspección
- **Geocodificación** — Geocodio con cache SQLite

---

## Middleware Disponible

```php
// Autenticación
'auth'               // Web (sesión)
'auth.api'           // API (Passport token)
'auth.tracker'       // Tracker (protocolo GPS)
'auth.admin'         // Solo administradores
'auth.manager'       // Managers

// Funcional
'active_subscription' // Verifica suscripción activa
'captcha'            // Validación captcha
'throttle'           // Rate limiting
'confirmed_action'   // Acción confirmada (2FA-like)
'verified'           // Email verificado
```

---

## Rutas Principales

### Web (`routes/web.php`)

- `/` → Redirige a `objects.index` (autenticado) o login
- `/authentication/*` → Login/Logout con captcha y throttle
- `/azure/*` → SSO con Azure AD
- `/registration/*` → Registro de nuevos usuarios
- `/payments/{gateway}/webhook` → Webhooks de pagos (excluido CSRF)
- `/gpsdata_insert` → Inserción de datos GPS (excluido CSRF)

### API (`routes/api.php`)

- `GET devices_in_geofences` — Dispositivos dentro de geocercas
- `*_groups` — CRUD de grupos (devices, geofences, routes, pois)
- `get_tasks` / `add_task` / `edit_task` — Gestión de tareas
- `dashboard/statistics` — Estadísticas del dashboard
- `devices/{id}/media` — Fotos y archivos de dispositivos
- `api/login` — Login API con throttle
- `api/insert_position` — Inserción de posición GPS

---

## Configuraciones Clave

| Archivo                  | Descripción                                                           |
| ------------------------ | --------------------------------------------------------------------- |
| `config/tobuli.php`      | Config principal: versión, device colors, map controls, main_settings |
| `config/app.php`         | App name "SEO GPS", debug, URL, HTTPS forzado                         |
| `config/database.php`    | Conexiones DB (MySQL principal)                                       |
| `config/payments.php`    | Gateways de pago                                                      |
| `config/fcm.php`         | Firebase Cloud Messaging                                              |
| `config/maps.php`        | Proveedores de mapas                                                  |
| `config/limits.php`      | Límites de dispositivos/usuarios                                      |
| `config/permissions.php` | Sistema de permisos                                                   |
| `config/server.php`      | Throttle limits (login, password_reset, etc.)                         |
| `config/sms.php`         | Configuración SMS                                                     |
| `config/cors.php`        | CORS                                                                  |

---

## Variables de Entorno Relevantes

```env
APP_NAME=           # Nombre de la app
APP_ENV=            # local / production
APP_DEBUG=          # true/false
APP_URL=            # URL base
APP_TYPE=ss3        # Tipo de instalación
key=                # Clave de licencia
server=localhost    # Servidor Traccar
admin_user=admin    # Usuario admin por defecto
FORCE_HTTPS=false   # Forzar HTTPS
TRUST_HOSTS=        # Hosts confiables (separados por ;)
logs_path=          # Ruta logs Traccar
media_path=         # Ruta fotos dispositivos
GEOCODER_CACHE_DRIVER=sqlite
LOG_SEND_MAIL_TEMPLATE=false
POSITIONS_IP_LOG=true
```

---

## Helpers Globales (autoload)

```php
// Tobuli/Helpers/Helper.php         — helpers generales
// Tobuli/Helpers/NavigationHelper.php — navegación/menú
// Tobuli/Helpers/FormHelper.php     — helpers de formularios
// Tobuli/Helpers/TableHelper.php    — helpers de tablas
// Tobuli/Helpers/UTF8.php           — manejo UTF8
// Tobuli/Helpers/Settings/helper.php — función settings()
```

Función clave: `settings('main_settings.allow_users_registration')` — accede a configuración del sistema.

---

## Dependencias Notables

| Paquete                           | Uso                    |
| --------------------------------- | ---------------------- |
| `laravel/passport`                | OAuth2 para API        |
| `maatwebsite/excel`               | Exportación Excel      |
| `barryvdh/laravel-dompdf`         | PDF con DomPDF         |
| `barryvdh/laravel-snappy`         | PDF con wkhtmltopdf    |
| `yajra/laravel-datatables-oracle` | DataTables server-side |
| `league/fractal`                  | API transformers       |
| `stripe/stripe-php`               | Pagos Stripe           |
| `braintree/braintree_php`         | Pagos Braintree        |
| `plivo/plivo-php`                 | SMS Plivo              |
| `sendgrid/sendgrid`               | Email SendGrid         |
| `spatie/laravel-activitylog`      | Log de actividad       |
| `php-ffmpeg/php-ffmpeg`           | Conversión de video    |
| `simplesoftwareio/simple-qrcode`  | Generación QR          |
| `mews/captcha`                    | Captcha                |
| `geocodio/geocodio-library-php`   | Geocodificación        |
| `predis/predis`                   | Redis cliente          |
| `bugsnag/bugsnag-laravel`         | Error tracking         |

---

## Comandos Artisan Personalizados

El proyecto tiene comandos en `app/Console/Commands/`. Comandos de configuración post-instalación:

```bash
php artisan server:translations   # Traduciones del servidor
php artisan socket:ssl            # Config SSL para socket
php artisan socket:service        # Config servicio socket
```

---

## Convenciones de Código

1. **Controladores** usan ModalHelpers para lógica de formularios, ej: `DeviceModalHelper`
2. **Repositories** en `Tobuli/Repositories/` para acceso a datos (no usar DB:: directamente en controladores)
3. **Transformers** en `app/Transformers/` usando League Fractal para respuestas API
4. **Events/Listeners** para operaciones asíncronas (DevicePositionChanged, DeviceCreated, etc.)
5. **Jobs** para tareas en cola (envío de emails, conversión de video, importaciones)
6. **Facades personalizadas**: `CustomFacades\Appearance`, `CustomFacades\Language`, etc.
