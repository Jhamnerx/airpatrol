<?php

namespace Tobuli\Services\Jimi;

class JimiException extends \RuntimeException
{
    public ?array $rawResponse;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, ?array $rawResponse = null)
    {
        parent::__construct($message, $code, $previous);
        $this->rawResponse = $rawResponse;
    }

    /**
     * ¿Es un error de límite de peticiones de la API?
     * Ej: "Illegal access, too frequently request!" (código 1006)
     *     "Illegal access, request frequency is too high today!" (cuota diaria)
     */
    public function isRateLimit(): bool
    {
        return (bool) preg_match('/frequen/i', $this->getMessage());
    }

    /**
     * ¿Es un error de access_token inválido/expirado?
     * Solo en este caso tiene sentido renovar el token y reintentar.
     */
    public function isTokenError(): bool
    {
        return stripos($this->getMessage(), 'token') !== false;
    }
}
