<?php

namespace App\Http\Requests\Concerns;

trait HasVariantMessages
{
    use HasImageMessages;

    protected function variantMessages(): array {
        return array_merge([
            'sku.required'   => 'SKU is required.',
            'sku.unique'     => 'This SKU is already taken.',
            'price.required' => 'Variant price is required.',
            'price.numeric'  => 'Variant price must be numeric.',
            'price.min'      => 'Variant price must be at least 0.',
            'weight.numeric' => 'Weight must be numeric.',
            'weight.min'     => 'Weight must be at least 0.',
        ], $this->imageMessages());
    }
}
