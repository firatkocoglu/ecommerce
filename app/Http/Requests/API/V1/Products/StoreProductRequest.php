<?php

namespace App\Http\Requests\API\V1\Products;

use App\Http\Requests\Concerns\HasImageAfterHooks;
use App\Http\Requests\Concerns\HasProductDataPreparation;
use App\Http\Requests\Concerns\HasProductMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    use HasImageAfterHooks, HasProductDataPreparation, HasProductMessages;

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
    protected function prepareForValidation(): void
    {
        $this->prepareProductData();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')],
            'description' => ['nullable', 'string'],

            // Pricing & visibility
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'price' => ['required_without:variants', 'numeric', 'min:0'],
            'weight' => ['required', 'numeric', 'min:0'],

            // Attributes
            'attributes' => ['nullable', 'array'],

            // Relationships
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],

            // Images
            'images' => ['nullable', 'array'],
            'images.*.url' => ['required_with:images', 'string', 'max:2048', 'url'],
            'images.*.is_primary' => ['nullable', 'boolean'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],

            // Stock management
            'track_stock' => ['nullable', 'boolean'],
            'allow_backorder' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->applySinglePrimaryImageRule($validator);
    }

    public function messages(): array
    {
        return $this->productMessages();
    }
}
