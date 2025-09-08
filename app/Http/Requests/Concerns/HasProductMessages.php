<?php

namespace App\Http\Requests\Concerns;
use App\Http\Requests\Concerns\HasImageMessages;

trait HasProductMessages
{
    use HasImageMessages;

    public function productMessages(): array {
        return array_merge([
            'name.required' => 'The product name is required.',
            'name.string' => 'The product name must be a string.',
            'name.max' => 'The product name may not be greater than 255 characters.',

            'slug.string' => 'The product slug must be a string.',
            'slug.max' => 'The product slug may not be greater than 255 characters.',
            'slug.unique' => 'The product slug has already been taken.',

            'description.string' => 'The product description must be a string.',

            'price.required' => 'The product price is required.',
            'price.numeric' => 'The product price must be a number.',
            'price.min' => 'The product price must be at least 0.',

            'currency.string' => 'The currency must be a string.',
            'currency.size' => 'The currency must be a valid 3-letter ISO code. e.g. USD, EUR, GBP, TRY.',

            'status.in' => 'The status must be one of the following: draft, active, archived.',
            'visibility.in' => 'The visibility must be one of the following: public, private.',

            'category_id.required' => 'The category ID is required.',
            'category_id.integer' => 'The category ID must be an integer.',
            'category_id.exists' => 'The selected category does not exist.',

            'attributes.array' => 'The attributes must be an array.',
        ], $this->imageMessages());
    }
}
