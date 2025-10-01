<?php

namespace App\Services\ProductImages\DTO;

final class OwnerContext
{
    public function __construct(
        public readonly string $type,
        public readonly int $id,
    ) {}

    public function fk(): string
    {
        return $this->type === 'product'
            ? 'product_id'
            : 'product_variant_id';
    }
}
