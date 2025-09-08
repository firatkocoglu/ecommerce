<?php

namespace App\Http\Requests\Concerns;

trait HasImageMessages
{
    protected function imageMessages(): array
    {
        return [
            'images.array' => 'The images must be an array.',
            'images.*.url.required_with' => 'Each image must have a URL.',
            'images.*.url.string' => 'Each image URL must be a string.',
            'images.*.url.max' => 'Each image URL may not be greater than 2048 characters.',
            'images.*.is_primary.boolean' => 'The is_primary field must be true or false.',
            'images.*.sort_order.integer' => 'The sort_order field must be an integer.',
            'images.*.sort_order.min' => 'The sort_order field must be at least 0.',
        ];
    }
}
