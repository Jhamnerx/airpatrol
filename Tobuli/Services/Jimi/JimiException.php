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
}
