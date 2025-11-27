<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (OutOfStockException $e, $request) {
            return response()->json([
                'error' => 'OUT_OF_STOCK',
                'message' => $e->getMessage(),
            ], 409); // 422 status code
        });
    }
}
