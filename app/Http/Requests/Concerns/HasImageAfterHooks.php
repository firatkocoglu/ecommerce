<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait HasImageAfterHooks
{
    // Ensure only one image is marked as primary
    public function applySinglePrimaryImageRule(Validator $validator, string $imagesKey = 'images', string $flag = 'is_primary', string $message = 'Only one image can be marked as primary.'): void
    {
        // After validation hook to check for multiple primary images in the images array
        $validator->after(function ($validator) use ($imagesKey, $flag, $message) {
            $images = collect($this->input($imagesKey, []));
            $primaryCount = $images->where($flag, true)->count();
            if ($primaryCount > 1) {
                $validator->errors()->add($imagesKey, $message);
            }
        });

        
    }
}
