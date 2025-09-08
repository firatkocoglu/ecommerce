<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Foundation\Http\FormRequest;

trait HasProductDataPreparation
{
    protected function prepareProductData(): void {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'slug' => is_string($this->input('slug')) ? trim($this->input('slug')) : $this->input('slug'),
            'status' => is_string($this->input('status')) ? strtolower(trim($this->input('status'))) : $this->input('status'),
            'visibility' => is_string($this->input('visibility')) ? strtolower(trim($this->input('visibility'))) : $this->input('visibility'),
            'currency' => is_string($this->input('currency')) ? strtoupper(trim($this->input('currency'))) : $this->input('currency'),
        ]);
    }
}
