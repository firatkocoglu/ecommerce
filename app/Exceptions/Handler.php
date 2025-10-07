<?php

use App\Exceptions\OutOfStockException;
use NunoMaduro\Collision\Adapters\Laravel\ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (OutOfStockException $e, $request) {
            return response()->json([
                'error' => 'OUT_OF_STOCK',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        });
    }
}
