<?php

namespace App\Http\Requests\Concerns;

trait HasVariantDataPreparation
{
    protected function prepareVariantData(): void
    {
        $this->merge([
            'sku' => is_string($this->input('sku')) ? trim($this->input('sku')) : $this->input('sku'),
            'weight' => (float) $this->input('weight'),
            'price' => (int) round((float) $this->input('price') * 100),
        ]);
    }
}
