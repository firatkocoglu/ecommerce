<?php

namespace App\Http\Requests\API\V1\Products;

use App\Http\Requests\Concerns\HasImageAfterHooks;
use App\Http\Requests\Concerns\HasProductDataPreparation;
use App\Http\Requests\Concerns\HasProductMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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

    public function rules(): array
    {
        $productId = $this->route('id') ?? $this->route('product');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => ['sometimes', 'nullable', 'string'],

            // Pricing & weight
            'price' => ['sometimes','required', function ($attribute, $value, $fail) {
                if ($value === "") {
                    $fail("The {$attribute} field cannot be empty.");
                }
            }, 'numeric', 'min:0'],
            'weight' => ['sometimes', 'required', function ($attribute, $value, $fail) {
                if ($value === "") {
                    $fail("The {$attribute} field cannot be empty.");
                }
            }, 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', Rule::in(['draft', 'active', 'archived'])],

            // Relationships
            'categories' => ['sometimes', 'required', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],

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

    public function messages(): array
    {
        return $this->productMessages();
    }
}
