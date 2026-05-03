<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tobuli\Services\Jimi\JimiAlarmService;
use Tobuli\Services\Jimi\JimiAuthService;
use Tobuli\Services\Jimi\JimiClient;
use Tobuli\Services\Jimi\JimiDeviceService;

/**
 * Comando de depuración para probar los endpoints de Jimi API directamente.
 *
 * Uso:
 *   php artisan jimi:debug live-stream {imei}
 *   php artisan jimi:debug token
 *   php artisan jimi:debug raw {method} {--param=*}
 */
class JimiDebug extends Command
{
    protected $signature = 'jimi:debug
                            {action : Acción: token | accounts | devices | live-stream | raw | test-account | push-test}
                            {imei?  : IMEI del dispositivo (requerido para live-stream y push-test)}
                            {--method= : Método Jimi para usar con raw}
                            {--param=* : Params extra en formato key=value para raw}
                            {--channel=1 : Canal de cámara}
                            {--target= : Cuenta target para subcuentas/devices (por defecto usa JIMI_ACCOUNT)}
                            {--flat : Mostrar lista plana en vez de árbol}
                            {--user= : Usuario para test-account}
                            {--pass= : Contraseña (texto plano) para test-account}
                            {--alarm-type=17 : Tipo de alarma para push-test (ver apéndice Jimi)}';

    protected $description = 'Herramienta de depuración para la API de Jimi IoT';

    public function handle(JimiAuthService $auth, JimiClient $client, JimiDeviceService $deviceService): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'token':
                return $this->testToken($auth, $client);

            case 'accounts':
                return $this->testAccounts($auth, $deviceService);

            case 'devices':
                return $this->testDevices($auth, $client, $deviceService);

            case 'live-stream':
                return $this->testLiveStream($auth, $client);

            case 'raw':
                return $this->testRaw($auth, $client);

            case 'test-account':
                return $this->testAccount($client);

            case 'push-test':
                return $this->testPush();

            default:
                $this->error("Acción desconocida: {$action}. Usa: token | accounts | devices | live-stream | raw | test-account | push-test");
                return self::FAILURE;
        }
    }

    protected function testToken(JimiAuthService $auth, JimiClient $client): int
    {
        $this->info('[Jimi] Probando obtención de token...');
        $this->printConfig();

        $account = (string) config('jimi.account');
        $pwd     = (string) config('jimi.password', '');
        $pwdMd5  = preg_match('/^[0-9a-f]{32}$/i', $pwd) ? strtolower($pwd) : md5($pwd);
        $ttl     = (int) config('jimi.token_ttl', 7000);

        $this->line('');
        $this->line('  <fg=yellow>account config:</> ' . $account);
        $this->line('  <fg=yellow>user_pwd_md5:</>  ' . $pwdMd5);
        $this->line('  <fg=yellow>expires_in:</>    ' . $ttl);
        $this->line('');

        try {
            // Borrar cache y llamar directamente para ver la respuesta completa
            $auth->forgetToken();

            $result = $client->send('jimi.oauth.token.get', [
                'user_id'      => $account,
                'user_pwd_md5' => $pwdMd5,
                'expires_in'   => $ttl,
            ]);

            $this->info('[Jimi] Respuesta completa de jimi.oauth.token.get:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $returnedAccount = $result['account'] ?? 'desconocido';
            $accessToken     = $result['accessToken'] ?? $result['access_token'] ?? '—';
            $refreshToken    = $result['refreshToken'] ?? '';

            // Guardar en caché para que otros comandos no tengan que re-autenticar (evita 1006)
            if ($accessToken && $accessToken !== '—') {
                \Illuminate\Support\Facades\Cache::put(JimiAuthService::CACHE_KEY_ACCESS,  $accessToken,  $ttl - 60);
                \Illuminate\Support\Facades\Cache::put(JimiAuthService::CACHE_KEY_REFRESH, $refreshToken, $ttl + 3600);
                $this->line('  <fg=gray>[cache actualizado]</>');
            }

            $this->line('');
            if (strcasecmp($returnedAccount, $account) !== 0) {
                $this->warn("[!] El token fue emitido para '{$returnedAccount}' pero JIMI_ACCOUNT='{$account}'");
            } else {
                $this->info("[OK] Token emitido para la cuenta correcta: {$returnedAccount}");
            }
            $this->line('  access_token = ' . $accessToken);
        } catch (\Throwable $e) {
            $this->error('[Jimi] Error: ' . $e->getMessage());
            $this->warn('Código: ' . $e->getCode());
            if (method_exists($e, 'rawResponse') || property_exists($e, 'rawResponse')) {
                $this->line('Raw: ' . json_encode($e->rawResponse, JSON_PRETTY_PRINT));
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function testAccounts(JimiAuthService $auth, JimiDeviceService $deviceService): int
    {
        $target = $this->option('target') ?: config('jimi.account');

        $this->info("[Jimi] Árbol de cuentas y subcuentas de: {$target}");
        $this->printConfig();

        try {
            $auth->getAccessToken(); // warm-up

            if ($this->option('flat')) {
                // Lista plana de subcuentas directas
                $subs = $deviceService->listSubAccounts($target);
                if (empty($subs)) {
                    $this->warn("  Sin subcuentas en: {$target}");
                } else {
                    $this->info('  Total subcuentas: ' . count($subs));
                    $rows = [];
                    foreach ($subs as $s) {
                        $rows[] = [
                            $s['account']     ?? '—',
                            $s['name']        ?? '—',
                            $s['type']        ?? '—',
                            $s['companyName'] ?? '—',
                            $s['email']       ?? '—',
                            $s['phone']       ?? '—',
                            ($s['enabledFlag'] ?? 1) ? 'Activo' : 'Inactivo',
                        ];
                    }
                    $this->table(['Cuenta', 'Nombre', 'Tipo', 'Empresa', 'Email', 'Tel', 'Estado'], $rows);
                    $this->line("\nRaw completo:");
                    $this->line(json_encode($subs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            } else {
                // Árbol completo con dispositivos
                $tree = $deviceService->buildAccountTree($target);
                $this->printAccountTree($tree, 0);
            }
        } catch (\Throwable $e) {
            $this->error('[Jimi] Error: ' . $e->getMessage() . ' (código: ' . $e->getCode() . ')');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Imprime el árbol de cuentas de forma recursiva.
     */
    private function printAccountTree(array $node, int $depth): void
    {
        $indent  = str_repeat('  ', $depth);
        $account = $node['account'] ?? '?';
        $devices = $node['devices'] ?? [];
        $children = $node['children'] ?? [];

        $icon = $depth === 0 ? '★' : '└─';
        $this->line("<fg=cyan>{$indent}{$icon} {$account}</> ({$this->countAllDevices($node)} dispositivo(s) total)");

        // Tabla de dispositivos directos
        if (!empty($devices)) {
            $rows = [];
            foreach ($devices as $d) {
                $rows[] = [
                    $d['imei']       ?? '—',
                    $d['deviceName'] ?? '—',
                    $d['mcType']     ?? '—',
                    $d['expiration'] ?? '—',
                    ($d['enabledFlag'] ?? 1) ? 'Activo' : 'Inact.',
                ];
            }
            $this->table(['IMEI', 'Nombre', 'Modelo', 'Expiración', 'Estado'], $rows);
        } else {
            $this->line("{$indent}  (sin dispositivos directos)");
        }

        // Subcuentas
        foreach ($children as $child) {
            $this->printAccountTree($child, $depth + 1);
        }
    }

    private function countAllDevices(array $node): int
    {
        $count = count($node['devices'] ?? []);
        foreach ($node['children'] ?? [] as $child) {
            $count += $this->countAllDevices($child);
        }
        return $count;
    }

    protected function testDevices(JimiAuthService $auth, JimiClient $client, JimiDeviceService $deviceService): int
    {
        $target = $this->option('target') ?: config('jimi.account');
        $flat   = $this->option('flat');

        $this->info("[Jimi] Dispositivos" . ($flat ? " (solo cuenta directa)" : " (toda la jerarquía)") . " de: {$target}");
        $this->printConfig();

        try {
            $auth->getAccessToken(); // warm-up cache

            if ($flat) {
                // Un solo nivel: solo la cuenta indicada
                $devices   = $deviceService->listDevicesByAccount($target);
                $locations = $this->getLocationsMap($client, $auth, $target);
            } else {
                // Toda la jerarquía (cuenta raíz + subcuentas)
                $devices   = $deviceService->listAllDevicesRecursive($target);
                $locations = $this->getAllLocationsMap($client, $auth, $deviceService, $target);
            }

            if (empty($devices)) {
                $this->warn('  Sin dispositivos encontrados.');
                return self::SUCCESS;
            }

            $this->info('  Total dispositivos: ' . count($devices));

            $rows = [];
            foreach ($devices as $d) {
                $imei     = $d['imei'] ?? '—';
                $loc      = $locations[$imei] ?? [];
                $rows[] = [
                    $imei,
                    $d['deviceName']  ?? '—',
                    $d['mcType']      ?? '—',
                    $d['_account']    ?? $target,
                    $loc['lat']       ?? '—',
                    $loc['lng']       ?? '—',
                    $loc['gpsTime']   ?? ($loc['hbTime'] ?? '—'),
                    ($loc['accStatus'] ?? '0') === '1' ? 'ON' : 'off',
                ];
            }

            $this->table(
                ['IMEI', 'Nombre', 'Modelo', 'Cuenta', 'Lat', 'Lng', 'GPS Time', 'ACC'],
                $rows
            );
        } catch (\Throwable $e) {
            $this->error('[Jimi] Error: ' . $e->getMessage() . ' (código: ' . $e->getCode() . ')');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** Obtiene un mapa [imei => location] para una cuenta específica */
    private function getLocationsMap(JimiClient $client, JimiAuthService $auth, string $target): array
    {
        try {
            $locations = $client->send('jimi.user.device.location.list', [
                'access_token' => $auth->getAccessToken(),
                'target'       => $target,
            ]);
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        foreach ($locations as $loc) {
            if (!empty($loc['imei'])) {
                $map[$loc['imei']] = $loc;
            }
        }
        return $map;
    }

    /** Obtiene mapa [imei => location] de toda la jerarquía de cuentas */
    private function getAllLocationsMap(JimiClient $client, JimiAuthService $auth, JimiDeviceService $deviceService, string $rootAccount): array
    {
        // Obtener todas las cuentas (raíz + subcuentas planas)
        $accounts = [$rootAccount];
        $subs     = $deviceService->listSubAccounts($rootAccount);
        foreach ($subs as $sub) {
            if (!empty($sub['account'])) {
                $accounts[] = $sub['account'];
            }
        }

        $map = [];
        foreach ($accounts as $acc) {
            $accMap = $this->getLocationsMap($client, $auth, $acc);
            $map    = array_merge($map, $accMap);
        }
        return $map;
    }

    protected function testLiveStream(JimiAuthService $auth, JimiClient $client): int
    {
        $imei = $this->argument('imei');

        if (!$imei) {
            $this->error('El IMEI es requerido para live-stream. Ej: php artisan jimi:debug live-stream 868120145233604');
            return self::FAILURE;
        }

        $this->info("[Jimi] Probando jimi.device.live.page.url para IMEI: {$imei}");
        $this->printConfig();

        try {
            $token = $auth->getAccessToken();
            $this->line("  access_token = {$token}");

            $params = [
                'access_token' => $token,
                'imei'         => $imei,
                'type'         => '1',
                'voice'        => '1',
            ];

            $this->info('[Jimi] Parámetros de la petición:');
            $this->table(['Key', 'Value'], collect($params)->map(fn($v, $k) => [$k, $v])->values()->toArray());

            // Mostrar el sign generado
            $sign = JimiClient::generateSign(array_merge([
                'method'      => 'jimi.device.live.page.url',
                'app_key'     => config('jimi.app_key'),
                'sign_method' => 'md5',
                'timestamp'   => date('Y-m-d H:i:s'),
                'format'      => 'json',
                'v'           => '1.0',
            ], $params), config('jimi.app_secret'));

            $this->line("  sign generado = {$sign}");

            $result = $client->send('jimi.device.live.page.url', $params);

            $this->info('[Jimi] Respuesta exitosa:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            $this->error('[Jimi] Error: ' . $e->getMessage() . ' (código: ' . $e->getCode() . ')');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function testRaw(JimiAuthService $auth, JimiClient $client): int
    {
        $method = $this->option('method');

        if (!$method) {
            $this->error('Debes indicar --method. Ej: --method=jimi.device.live.page.url');
            return self::FAILURE;
        }

        // Parsear --param key=value
        $params = [];
        foreach ($this->option('param') as $p) {
            [$k, $v] = explode('=', $p, 2);
            $params[$k] = $v;
        }

        // Solo inyectar token cacheado si NO se pasó uno explícito via --param
        if (empty($params['access_token'])) {
            $params['access_token'] = $auth->getAccessToken();
        } else {
            $this->warn('  (usando access_token proporcionado via --param, no el cacheado)');
        }

        $this->info("[Jimi] Ejecutando método: {$method}");
        $this->info('[Jimi] Parámetros:');
        $this->table(['Key', 'Value'], collect($params)->map(fn($v, $k) => [$k, $v])->values()->toArray());

        try {
            $result = $client->send($method, $params);
            $this->info('[Jimi] Respuesta:');
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            $this->error('[Jimi] Error: ' . $e->getMessage() . ' (código: ' . $e->getCode() . ')');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Prueba credenciales de una cuenta alternativa y verifica acceso a dispositivos/live-stream.
     *
     * Uso:
     *   php artisan jimi:debug test-account --user=airpatrol --pass=airpatrol2026
     *   php artisan jimi:debug test-account --user=airpatrol --pass=airpatrol2026 {imei}
     */
    protected function testAccount(JimiClient $client): int
    {
        $user = $this->option('user');
        $pass = $this->option('pass');

        if (!$user || !$pass) {
            $this->error('Debes indicar --user=<usuario> y --pass=<contraseña>');
            $this->line('Ejemplo: php artisan jimi:debug test-account --user=airpatrol --pass=airpatrol2026');
            return self::FAILURE;
        }

        $this->info("[Jimi] Probando cuenta: {$user}");
        $this->printConfig();

        // 1. Login con las credenciales indicadas (mismo método que JimiAuthService::fetchNewToken)
        $this->info('── Paso 1: jimi.oauth.token.get ─────────────────────');
        try {
            $loginResult = $client->send('jimi.oauth.token.get', [
                'user_id'      => $user,
                'user_pwd_md5' => md5($pass), // minúsculas
                'expires_in'   => 7000,
            ]);
            $token = $loginResult['accessToken'] ?? null;
            $this->line(json_encode($loginResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Tobuli\Services\Jimi\JimiException $e) {
            $this->error('Login fallido: ' . $e->getMessage() . ' (código: ' . $e->getCode() . ')');
            if ($e->rawResponse) {
                $this->line(json_encode($e->rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            return self::FAILURE;
        }

        if (!$token) {
            $this->error('No se obtuvo accessToken en la respuesta.');
            return self::FAILURE;
        }

        $this->info("  access_token = {$token}");

        // 2. Listar subcuentas visibles para este token
        $this->info('── Paso 2a: jimi.user.child.list (subcuentas visibles) ──');
        $visibleAccounts = [$user];
        try {
            $subs = $client->send('jimi.user.child.list', [
                'access_token' => $token,
                'target'       => $user,
            ]);
            $this->info('  Subcuentas: ' . count($subs));
            foreach ($subs as $s) {
                $acc = $s['account'] ?? null;
                if ($acc) {
                    $visibleAccounts[] = $acc;
                    $this->line('    - ' . $acc . ' (enabledFlag=' . ($s['enabledFlag'] ?? '?') . ')');
                }
            }
        } catch (\Tobuli\Services\Jimi\JimiException $e) {
            $this->warn('  child.list falló: ' . $e->getMessage() . ' (código: ' . $e->getCode() . ')');
        }

        // 2b. Listar devices de cada cuenta visible
        $this->info('── Paso 2b: jimi.user.device.list (por cada cuenta) ────');
        foreach ($visibleAccounts as $acc) {
            try {
                $devices = $client->send('jimi.user.device.list', [
                    'access_token' => $token,
                    'target'       => $acc,
                ]);
                $this->line("  [{$acc}] Dispositivos: " . count($devices));
                if (!empty($devices)) {
                    $rows = [];
                    foreach ($devices as $d) {
                        $rows[] = [$d['imei'] ?? '—', $d['deviceName'] ?? '—', $d['mcType'] ?? '—'];
                    }
                    $this->table(['IMEI', 'Nombre', 'Modelo'], $rows);
                }
            } catch (\Tobuli\Services\Jimi\JimiException $e) {
                $this->warn("  [{$acc}] device.list falló: " . $e->getMessage() . ' (código: ' . $e->getCode() . ')');
            }
        }

        // 3. Probar live-stream si se pasó IMEI
        $imei = $this->argument('imei');
        if ($imei) {
            $this->info("── Paso 3: jimi.device.live.page.url ({$imei}) ────────");
            try {
                $stream = $client->send('jimi.device.live.page.url', [
                    'access_token' => $token,
                    'imei'         => $imei,
                    'type'         => '1',
                    'voice'        => '1',
                ]);
                $this->info('[OK] Respuesta live-stream:');
                $this->line(json_encode($stream, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } catch (\Tobuli\Services\Jimi\JimiException $e) {
                $this->error('live-stream falló: ' . $e->getMessage() . ' (código: ' . $e->getCode() . ')');
                if ($e->rawResponse) {
                    $this->line(json_encode($e->rawResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }
        } else {
            $this->warn('(Pasa el IMEI como argumento para probar live-stream también)');
        }

        return self::SUCCESS;
    }

    protected function testPush(): int
    {
        $imei      = $this->argument('imei') ?: '869247060381354';
        $alarmType = $this->option('alarm-type') ?: '17';

        $alarmNames = \Tobuli\Services\Jimi\JimiAlarmService::ALARM_MAP;
        $alarmName  = $alarmNames[$alarmType] ?? 'unknown';

        $payload = json_encode([
            'imei'       => $imei,
            'deviceName' => $imei,
            'alarmType'  => $alarmType,
            'alarmName'  => ucwords(str_replace('_', ' ', $alarmName)),
            'lat'        => '-24.811716',
            'lng'        => '-57.782728',
            'alarmTime'  => now()->format('Y-m-d H:i:s'),
        ]);

        $this->info('[Jimi Push Test] Simulando push al webhook...');
        $this->line("  IMEI:       {$imei}");
        $this->line("  alarmType:  {$alarmType}");
        $this->line("  alarmName:  {$alarmName}");
        $this->line("  payload:    {$payload}");
        $this->line('');

        $webhookUrl = url('/jimi/webhook');
        $this->line("  POST → {$webhookUrl}");
        $this->line('');

        $response = \Illuminate\Support\Facades\Http::asForm()->post($webhookUrl, [
            'msgType' => 'jimi.push.device.alarm',
            'data'    => $payload,
        ]);

        $this->info("  HTTP Status: {$response->status()}");
        $this->line("  Body: " . $response->body());

        if ($response->successful()) {
            $this->info('[OK] Webhook respondió correctamente.');
        } else {
            $this->error('[!] Webhook devolvió un error HTTP.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function printConfig(): void
    {
        $this->line('');
        $this->line('Configuración actual:');
        $this->table(['Clave', 'Valor'], [
            ['JIMI_URL',        config('jimi.url')        ?: '(vacío)'],
            ['JIMI_ACCOUNT',    config('jimi.account')    ?: '(vacío)'],
            ['JIMI_APP_KEY',    config('jimi.app_key')    ?: '(vacío)'],
            ['JIMI_APP_SECRET', config('jimi.app_secret') ? str_repeat('*', 6) . substr(config('jimi.app_secret'), -4) : '(vacío)'],
        ]);
        $this->line('');
    }
}
