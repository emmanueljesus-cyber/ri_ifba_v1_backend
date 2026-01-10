<?php

namespace App\Exceptions;

use Exception;

/**
 * Exceção base para regras de negócio
 */
class BusinessException extends Exception
{
    protected array $meta = [];

    public function __construct(string $message, int $code = 400, array $meta = [])
    {
        parent::__construct($message, $code);
        $this->meta = $meta;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
