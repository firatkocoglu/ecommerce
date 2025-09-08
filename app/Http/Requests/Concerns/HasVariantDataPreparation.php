<?php

namespace App\Http\Requests\Concerns;

trait HasVariantDataPreparation
{
    protected function prepareVariantData(): void
    {
        $this->merge([
            'sku' => is_string($this->input('sku')) ? trim($this->input('sku')) : $this->input('sku'),
            'weight' => $this->filled('weight') ? (float) $this->input('weight') : null,
            'price' => $this->filled('price') ? (float) $this->input('price') : null,
        ]);
    }
}
