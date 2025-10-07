<?php

namespace App\Exceptions;

use RuntimeException;

class OutOfStockException extends RuntimeException
{
    public function __construct($message = "The requested item is out of stock.", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
