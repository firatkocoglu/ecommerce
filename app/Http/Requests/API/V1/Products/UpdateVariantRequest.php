<?php

namespace App\Http\Requests\API\V1\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Requests\Concerns\HasVariantMessages;
use App\Http\Requests\Concerns\HasImageAfterHooks;


class UpdateVariantRequest extends FormRequest
{
    use HasVariantMessages, HasImageAfterHooks;

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

            // Images
            'images' => ['sometimes', 'nullable', 'array'],
            'images.*.url' => ['sometimes', 'nullable', 'string', 'max:2048', 'url'],
            'images.*.is_primary' => ['sometimes', 'nullable', 'boolean'],
            'images.*.sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void {
        $this->applySinglePrimaryImageRule($validator);
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return $this->variantMessages();
    }
}
