<?php

namespace App\Http\Requests\API\V1\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void {
        $this->merge([
            'sku' => is_string($this->input('sku')) ? trim((string) $this->input('sku')) : $this->input('sku'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $variantId = $this->route('id') ?? $this->route('variant');

        return [
            'sku' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'options' => ['sometimes', 'nullable', 'array'],
        ];
    }


    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'sku.required'   => 'SKU is required when provided.',
            'sku.unique'     => 'This SKU is already taken.',
            'price.required' => 'Variant price is required when provided.',
            'price.numeric'  => 'Variant price must be numeric.',
            'price.min'      => 'Variant price must be at least 0.',
            'weight.numeric' => 'Weight must be numeric.',
            'weight.min'     => 'Weight must be at least 0.',
        ];
    }
}
