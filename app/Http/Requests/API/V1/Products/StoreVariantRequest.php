<?php

namespace App\Http\Requests\API\V1\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    protected function prepareForValidation(): void {
        $this->merge([
            'sku' => is_string($this->input('sku')) ? trim($this->input('sku')) : $this->input('sku'),
        ]);
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'weight' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'options' => ['nullable', 'array'],

            // Images
            'images' => ['nullable', 'array'],
            'images.*.url' => ['required_with:images', 'string', 'max:2048', 'url'],
            'images.*.is_primary' => ['nullable', 'boolean'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function after(): array {
        return [
            function ($validator) {
                $primaryCount = collect($this->input('images', []))
                    ->where('is_primary', true)
                    ->count();
                if ($primaryCount >  1) {
                    $validator->errors()->add('images', 'Only one image can be marked as primary.');
                }
            }
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'SKU is required.',
            'sku.unique'   => 'This SKU is already in use.',
            'price.required' => 'Variant price is required.',
            'price.numeric'  => 'Variant price must be numeric.',
            'weight.numeric' => 'Variant weight must be numeric.',
            'options.array' => 'Variant options must be array.',

            // Images
            'images.array' => 'The images must be an array.',
            'images.*.url.required_with' => 'Each image must have a URL when images are provided.',
            'images.*.url.string' => 'Each image URL must be a string.',
            'images.*.url.max' => 'Each image URL may not be greater than 2048 characters.',
            'images.*.is_primary.boolean' => 'The is_primary field must be true or false.',
            'images.*.sort_order.integer' => 'The sort_order field must be an integer.',
            'images.*.sort_order.min' => 'The sort_order field must be at least 0.',
        ];
    }
}
