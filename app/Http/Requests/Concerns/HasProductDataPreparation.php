<?php

namespace App\Http\Requests\Concerns;

trait HasProductDataPreparation
{
    protected function prepareProductData(): void
    {

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'slug' => is_string($this->input('slug')) ? trim($this->input('slug')) : $this->input('slug'),
            'status' => is_string($this->input('status')) ? strtolower(trim($this->input('status'))) : $this->input('status'),
            'weight' => $this->filled('weight') ? (float) $this->input('weight') : null,
            'price' => $this->filled('price') ? (float) $this->input('price') : null,
        ]);
    }
}
