<?php

namespace App\Http\Requests\API\V1\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Requests\Concerns\HasProductMessages;
use App\Http\Requests\Concerns\HasImageAfterHooks;
use App\Http\Requests\Concerns\HasProductDataPreparation;


class UpdateProductRequest extends FormRequest
{
    use HasProductMessages, HasImageAfterHooks;
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
        $this->prepareForValidation();
    }

    public function rules(): array
    {
        $productId = $this->route('id') ?? $this->route('product');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => ['sometimes', 'nullable', 'string'],

            // Pricing & visibility
            'price' => ['sometimes', 'numeric', 'min:0', 'decimal:0,2'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'status' => ['sometimes', 'nullable', Rule::in(['draft', 'active', 'archived'])],
            'visibility' => ['sometimes', 'nullable', Rule::in(['public', 'private'])],

            // Relationships
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],

            // Attributes
            'attributes' => ['sometimes', 'nullable', 'array'],

            // Images
            'images' => ['sometimes', 'nullable', 'array'],
            'images.*.url' => ['sometimes', 'required_with:images', 'string', 'url', 'max:2048'],
            'images.*.is_primary' => ['sometimes', 'nullable', 'boolean'],
            'images.*.sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->applySinglePrimaryImageRule($validator);
    }

    public function messages(): array {
        return $this->productMessages();
    }
}
