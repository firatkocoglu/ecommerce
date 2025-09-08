<?php

namespace App\Http\Requests\API\V1\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Requests\Concerns\HasProductMessages;
use App\Http\Requests\Concerns\HasImageAfterHooks;
use App\Http\Requests\Concerns\HasProductDataPreparation;

class StoreProductRequest extends FormRequest
{
    use HasProductMessages, HasImageAfterHooks, HasProductDataPreparation;
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
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')],
            'description' => ['nullable', 'string'],

            // Pricing & visibility
            'price' => ['required_without:variants', 'numeric', 'min:0', 'decimal:0,2'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
            'visibility' => ['nullable', Rule::in(['public', 'private'])],

            // Relationships
            'category_id' => ['required', 'integer', 'exists:categories,id'],


            // Attributes
            'attributes' => ['nullable', 'array'],

            // Images
            'images' => ['nullable', 'array'],
            'images.*.url' => ['required_with:images', 'string', 'max:2048', 'url'],
            'images.*.is_primary' => ['nullable', 'boolean'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void {
        $this->applySinglePrimaryImageRule($validator);
    }

    public function messages(): array {
        return $this->productMessages();
    }

}
