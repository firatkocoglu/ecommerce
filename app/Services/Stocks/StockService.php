<?php

namespace App\Services\Stocks;

use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function getStockByProduct(int $productId): ?int
    {
        return Stock::where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->value('quantity');
    }

    public function getStockByVariant(int $variantId): ?int
    {
        return Stock::where('product_variant_id', $variantId)
            ->value('quantity');
    }

    public function hasSufficientStockForProduct(int $productId, int $requiredQuantity): bool
    {
        $stock = Stock::where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->value('quantity');

        return (int) ($stock ?? 0) >= $requiredQuantity;
    }

    public function hasSufficientStockForVariant(int $variantId, int $requiredQuantity): bool
    {
        $stock = Stock::where('product_variant_id', $variantId)
            ->value('quantity');

        return (int) ($stock ?? 0) >= $requiredQuantity;

    }

    public function decreaseStockForProduct(int $productId, int $quantity): void
    {
        $affected = DB::update('
            UPDATE stocks SET quantity = quantity - ?
            WHERE product_id = ?
            AND product_variant_id IS NULL
            AND quantity >= ?',
            [$quantity, $productId, $quantity]
        );

        if ($affected !== 1) {
            throw new \RuntimeException('Insufficient stock or product not found.');
        }
    }

    public function increaseStockForProduct(int $productId, int $quantity): void
    {
        $affected = DB::update('
            UPDATE stocks SET quantity = quantity + ?
            WHERE product_id = ?
            AND product_variant_id IS NULL',
            [$quantity, $productId]
        );

        if ($affected !== 1) {
            throw new \RuntimeException('Product not found.');
        }
    }

    public function decreaseStockForVariant(int $variantId, int $quantity): void
    {
        $affected = DB::update('
            UPDATE stocks SET quantity = quantity - ?
            WHERE product_variant_id = ?
            AND quantity >= ?',
            [$quantity, $variantId, $quantity]
        );

        if ($affected !== 1) {
            throw new \RuntimeException('Insufficient stock or variant not found.');
        }
    }

    public function increaseStockForVariant(int $variantId, int $quantity): void
    {
        $affected = DB::update('
            UPDATE stocks SET quantity = quantity + ?
            WHERE product_variant_id = ?',
            [$quantity, $variantId]
        );

        if ($affected !== 1) {
            throw new \RuntimeException('Variant not found.');
        }
    }
}
