<?php

namespace App\Exceptions;

use RuntimeException;

class OutOfStockException extends RuntimeException
{
    protected array $violations;

    public function __construct(string      $message = 'The requested item is out of stock.',
                                array       $violations = [],
                                int         $code = 409,
                                ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $violations, $previous);
    }

    public function violations(): array
    {
        return $this->violations;
    }
}
