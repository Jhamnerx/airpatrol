<?php

namespace Tobuli\Services\Jimi;

use Illuminate\Support\Facades\Cache;
use Tobuli\Entities\Device;

/**
 * Servicio de dispositivos Jimi IoT. *
 * Consulta dispositivos y subcuentas en la plataforma Jimi y los cruza con la
 * tabla `devices` local usando el campo IMEI (NO crea nuevas tablas).
 *
 * Jerarquía de cuentas Jimi:
 *   cuenta_raíz (target = config('jimi.account'))
 *   ├── dispositivos directos  → jimi.user.device.list (target=cuenta_raíz)
 *   └── subcuentas             → jimi.user.child.list  (target=cuenta_raíz)
 *       └── dispositivos       → jimi.user.device.list (target=subcuenta)
 *
 * Métodos Jimi utilizados:
 *   - jimi.user.device.list    → listDevicesByAccount()
 *   - jimi.user.child.list     → listSubAccounts()
 *   - jimi.track.device.detail → getDeviceDetail()
 */
class JimiDeviceService
{
    protected JimiClient $client;
    protected JimiAuthService $auth;

    public function __construct(JimiClient $client, JimiAuthService $auth)
    {
        $this->client = $client;
        $this->auth   = $auth;
    }

    // -------------------------------------------------------------------------
    // Listado de dispositivos
    // -------------------------------------------------------------------------

    /**
     * Lista los dispositivos directos de la cuenta raíz configurada en .env.
     *
     * Equivale a llamar listDevicesByAccount(config('jimi.account')).
     *
     * @return array[]
     */
    public function listDevices(): array
    {
        return $this->listDevicesByAccount(config('jimi.account'));
    }

    /**
     * Lista los dispositivos directos de una cuenta específica.
     *
     * Método Jimi: jimi.user.device.list
     * Parámetro:   target = nombre de la cuenta
     *
     * @param  string $account  Cuenta Jimi (ej: "laguirre1313" o "interexpres")
     * @return array[]          Lista de dispositivos: imei, deviceName, mcType, sim, expiration, etc.
     */
    public function listDevicesByAccount(string $account): array
    {
        $result = $this->client->send('jimi.user.device.list', [
            'access_token' => $this->auth->getAccessToken(),
            'target'       => $account,
        ]);

        return is_array($result) ? $result : [];
    }

    /**
     * Lista TODOS los dispositivos de la cuenta raíz y TODAS sus subcuentas (recursivo).
     *
     * Cada dispositivo incluye el campo extra '_account' con la cuenta propietaria.
     *
     * @param  string|null $rootAccount  Cuenta raíz (null = config('jimi.account'))
     * @param  int         $maxDepth     Profundidad máxima de recursión (protección contra loops)
     * @return array[]                   Lista plana de todos los dispositivos
     */
    public function listAllDevicesRecursive(?string $rootAccount = null, int $maxDepth = 5): array
    {
        $account = $rootAccount ?? config('jimi.account');

        return $this->collectDevicesRecursive($account, $maxDepth, 0);
    }

    /**
     * @internal
     */
    private function collectDevicesRecursive(string $account, int $maxDepth, int $depth): array
    {
        // Dispositivos directos de esta cuenta
        $devices = $this->listDevicesByAccount($account);

        // Agregar el campo '_account' a cada dispositivo para trazabilidad
        foreach ($devices as &$d) {
            $d['_account'] = $account;
        }
        unset($d);

        // Si no llegamos al límite de profundidad, buscar subcuentas
        if ($depth < $maxDepth) {
            $subAccounts = $this->listSubAccounts($account);
            foreach ($subAccounts as $sub) {
                $subName = $sub['account'] ?? null;
                if ($subName && $subName !== $account) {
                    $subDevices = $this->collectDevicesRecursive($subName, $maxDepth, $depth + 1);
                    $devices    = array_merge($devices, $subDevices);
                }
            }
        }

        return $devices;
    }

    // -------------------------------------------------------------------------
    // Gestión de subcuentas
    // -------------------------------------------------------------------------

    /**
     * Lista las subcuentas directas de una cuenta.
     *
     * Método Jimi: jimi.user.child.list
     * Parámetro:   target = nombre de la cuenta padre
     *
     * Campos de respuesta:
     *   account, name, type (8=Distribuidor, 9=Usuario, 10=Dist.ordinario, 11=Ventas),
     *   displayFlag, companyName, email, phone, enabledFlag
     *
     * @param  string|null $parentAccount  Cuenta padre (null = config('jimi.account'))
     * @return array[]
     */
    public function listSubAccounts(?string $parentAccount = null): array
    {
        $target = $parentAccount ?? config('jimi.account');

        $result = $this->client->send('jimi.user.child.list', [
            'access_token' => $this->auth->getAccessToken(),
            'target'       => $target,
        ]);

        return is_array($result) ? $result : [];
    }

    /**
     * Construye el árbol completo de subcuentas y sus dispositivos.
     *
     * Estructura devuelta:
     * [
     *   'account'  => 'laguirre1313',
     *   'devices'  => [...],
     *   'children' => [
     *     [
     *       'account'  => 'interexpres',
     *       'devices'  => [...],
     *       'children' => [],
     *     ],
     *     ...
     *   ],
     * ]
     *
     * @param  string|null $rootAccount
     * @param  int         $maxDepth
     * @return array
     */
    public function buildAccountTree(?string $rootAccount = null, int $maxDepth = 5): array
    {
        $account = $rootAccount ?? config('jimi.account');

        return $this->buildTreeRecursive($account, $maxDepth, 0);
    }

    /**
     * @internal
     */
    private function buildTreeRecursive(string $account, int $maxDepth, int $depth): array
    {
        $node = [
            'account'  => $account,
            'devices'  => $this->listDevicesByAccount($account),
            'children' => [],
        ];

        if ($depth < $maxDepth) {
            $subAccounts = $this->listSubAccounts($account);
            foreach ($subAccounts as $sub) {
                $subName = $sub['account'] ?? null;
                if ($subName && $subName !== $account) {
                    $node['children'][] = array_merge(
                        $sub,
                        $this->buildTreeRecursive($subName, $maxDepth, $depth + 1)
                    );
                }
            }
        }

        return $node;
    }

    // -------------------------------------------------------------------------
    // Detalle y utilidades
    // -------------------------------------------------------------------------

    /**
     * Devuelve las cuentas disponibles en formato [account => label] para usar en un <select>.
     *
     * Incluye la cuenta raíz y todas sus subcuentas directas.
     * El resultado se cachea 5 minutos para no saturar la API al cargar formularios.
     *
     * Ejemplo de resultado:
     *   [
     *     ''             => '— Sin asignar —',
     *     'laguirre1313' => 'laguirre1313 (raíz)',
     *     'interexpres'  => 'interexpres',
     *   ]
     *
     * @param  bool $withEmpty  Incluir opción vacía "Sin asignar" al inicio
     * @return array<string, string>
     */
    public function getAccountsForSelect(bool $withEmpty = true): array
    {
        $root = config('jimi.account', '');

        $options = Cache::remember('jimi_accounts_for_select', 300, function () use ($root) {
            $list = [$root => $root . ' (raíz)'];

            try {
                $subs = $this->listSubAccounts($root);
                foreach ($subs as $sub) {
                    $name = $sub['account'] ?? null;
                    if ($name && $name !== $root) {
                        $label = $name;
                        if (!empty($sub['companyName'])) {
                            $label .= ' — ' . $sub['companyName'];
                        } elseif (!empty($sub['name']) && $sub['name'] !== $name) {
                            $label .= ' — ' . $sub['name'];
                        }
                        $list[$name] = $label;
                    }
                }
            } catch (\Throwable $e) {
                // Si la API falla, al menos mostramos la cuenta raíz
            }

            return $list;
        });

        if ($withEmpty) {
            return array_merge(['' => '— Sin asignar —'], $options);
        }

        return $options;
    }

    /**
     * Obtiene el detalle de un dispositivo específico por IMEI.
     *
     * Método Jimi: jimi.track.device.detail
     * Incluye: account, mcType, sim, expiration, vehicleName, VIN, iccid, etc.
     *
     * @param  string $imei
     * @return array
     */
    public function getDeviceDetail(string $imei): array
    {
        return $this->client->send('jimi.track.device.detail', [
            'access_token' => $this->auth->getAccessToken(),
            'imei'         => $imei,
        ]);
    }

    /**
     * Devuelve los IMEIs de Jimi (toda la jerarquía) que están en la tabla local `devices`.
     *
     * Se usa en SyncJimiPositions para filtrar solo dispositivos conocidos.
     *
     * @return string[]  Array de IMEIs
     */
    public function getRegisteredImeis(): array
    {
        $allDevices = $this->listAllDevicesRecursive();
        $jimiImeis  = array_filter(array_column($allDevices, 'imei'));

        if (empty($jimiImeis)) {
            return [];
        }

        return Device::whereIn('imei', $jimiImeis)
            ->whereNotNull('imei')
            ->pluck('imei')
            ->toArray();
    }
}
