<?php

namespace Tobuli\Services\Jimi;

use Illuminate\Support\Facades\Cache;

/**
 * Gestiona la autenticación con la API de Jimi IoT.
 *
 * - El access_token tiene vida útil de ~2h (configurable en jimi.token_ttl).
 * - Se cachea en Redis mediante el Cache facade de Laravel.
 * - NO solicitar token en cada request; reutilizar hasta que expire.
 *
 * Uso:
 *   $token = app(JimiAuthService::class)->getAccessToken();
 *
 * Desde Job/Command (auto-renovación):
 *   try {
 *       $token = $authService->getAccessToken();
 *   } catch (JimiException $e) {
 *       $authService->forgetToken();
 *       $token = $authService->getAccessToken(); // reintenta desde cero
 *   }
 */
class JimiAuthService
{
    const CACHE_KEY_ACCESS  = 'jimi_access_token';
    const CACHE_KEY_REFRESH = 'jimi_refresh_token';

    protected JimiClient $client;

    public function __construct(JimiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Devuelve el access_token vigente.
     * Si no hay token cacheado o expiró, obtiene uno nuevo de Jimi.
     */
    public function getAccessToken(): string
    {
        $token = Cache::get(self::CACHE_KEY_ACCESS);

        if ($token) {
            return $token;
        }

        return $this->fetchNewToken();
    }

    /**
     * Renueva el access_token usando el refresh_token.
     * Si no hay refresh_token disponible, obtiene uno desde cero.
     *
     * Método: jimi.oauth.token.refresh
     */
    public function refreshToken(): string
    {
        $ttl          = (int) config('jimi.token_ttl', 7000);
        $accessToken  = Cache::get(self::CACHE_KEY_ACCESS, '');
        $refreshToken = Cache::get(self::CACHE_KEY_REFRESH);

        if (!$refreshToken) {
            return $this->fetchNewToken();
        }

        $result = $this->client->send('jimi.oauth.token.refresh', [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => $ttl,
        ]);

        return $this->cacheTokens($result, $ttl);
    }

    /**
     * Elimina los tokens cacheados para forzar una nueva autenticación.
     * Útil cuando la API devuelve error de token inválido.
     */
    public function forgetToken(): void
    {
        Cache::forget(self::CACHE_KEY_ACCESS);
        Cache::forget(self::CACHE_KEY_REFRESH);
    }

    /**
     * Obtiene un nuevo token desde Jimi usando credenciales.
     *
     * Método: jimi.oauth.token.get
     * user_pwd_md5 = md5(password) en minúsculas.
     * Si JIMI_PASSWORD ya es el MD5 (32 chars hex), se usa directamente.
     */
    protected function fetchNewToken(): string
    {
        $ttl = (int) config('jimi.token_ttl', 7000);

        $pwd    = (string) config('jimi.password', '');
        $pwdMd5 = md5($pwd);

        $result = $this->client->send('jimi.oauth.token.get', [
            'user_id'      => config('jimi.account'),
            'user_pwd_md5' => $pwdMd5,
            'expires_in'   => $ttl,
        ]);

        return $this->cacheTokens($result, $ttl);
    }

    /**
     * Guarda access_token y refresh_token en cache.
     *
     * @param  array  $result  Array result[] de la respuesta Jimi
     * @param  int    $ttl     Segundos de vida del token
     * @return string          El access_token almacenado
     */
    protected function cacheTokens(array $result, int $ttl): string
    {
        $accessToken  = $result['accessToken']  ?? '';
        $refreshToken = $result['refreshToken'] ?? '';

        if (empty($accessToken)) {
            throw new JimiException('Jimi no devolvió accessToken en la respuesta');
        }

        // 60s de margen para evitar usar un token a punto de expirar
        Cache::put(self::CACHE_KEY_ACCESS,  $accessToken,  $ttl - 60);
        // El refresh_token puede durar más que el access_token
        Cache::put(self::CACHE_KEY_REFRESH, $refreshToken, $ttl + 3600);

        return $accessToken;
    }
}
