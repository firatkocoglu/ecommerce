<?php

namespace App\Http\Requests\API\V1\Variants;

use App\Http\Requests\Concerns\HasImageAfterHooks;
use App\Http\Requests\Concerns\HasVariantMessages;
use App\Http\Requests\Concerns\HasVariantDataPreparation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVariantRequest extends FormRequest
{
    use HasVariantMessages, HasImageAfterHooks, HasVariantDataPreparation;
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
        $this->prepareVariantData();
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', ],
            'weight' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'options' => ['nullable', 'array'],

            // Images
            'images' => ['nullable', 'array'],
            'images.*.url' => ['required_with:images', 'string', 'max:2048', 'url'],
            'images.*.is_primary' => ['nullable', 'boolean'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->applySinglePrimaryImageRule($validator);
    }

    public function messages(): array
    {
        return $this->variantMessages();
    }
}
