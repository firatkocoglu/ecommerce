<?php

namespace App\Http\Requests\API\V1\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'slug' => is_string($this->input('slug')) ? trim($this->input('slug')) : $this->input('slug'),
            'status' => is_string($this->input('status')) ? strtolower(trim($this->input('status'))) : $this->input('status'),
            'visibility' => is_string($this->input('visibility')) ? strtolower(trim($this->input('visibility'))) : $this->input('visibility'),
            'currency' => is_string($this->input('currency')) ? strtoupper(trim($this->input('currency'))) : $this->input('currency'),
        ]);
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

    public function messages(): array {
        return [
            'name.required' => 'The product name is required.',
            'name.string' => 'The product name must be a string.',
            'name.max' => 'The product name may not be greater than 255 characters.',

            'slug.string' => 'The product slug must be a string.',
            'slug.max' => 'The product slug may not be greater than 255 characters.',
            'slug.unique' => 'The product slug has already been taken.',

            'description.string' => 'The product description must be a string.',

            'price.required' => 'The product price is required.',
            'price.numeric' => 'The product price must be a number.',
            'price.min' => 'The product price must be at least 0.',

            'currency.string' => 'The currency must be a string.',
            'currency.size' => 'The currency must be a valid 3-letter ISO code. e.g. USD, EUR, GBP, TRY.',

            'status.in' => 'The status must be one of the following: draft, active, archived.',
            'visibility.in' => 'The visibility must be one of the following: public, private.',

            'category_id.required' => 'The category ID is required.',
            'category_id.integer' => 'The category ID must be an integer.',
            'category_id.exists' => 'The selected category does not exist.',

            'attributes.array' => 'The attributes must be an array.',

            'images.array' => 'The images must be an array.',
            'images.*.url.required_with' => 'Each image must have a URL when images are provided.',
            'images.*.url.string' => 'Each image URL must be a string.',
            'images.*.url.max' => 'Each image URL may not be greater than 2048 characters.',
            'images.*.is_primary.boolean' => 'The is_primary field must be true or false.',
            'images.*.sort_order.integer' => 'The sort_order field must be an integer.',
            'images.*.sort_order.min' => 'The sort_order field must be at least 0.',
        ];
    }

    public function after(): array {
        return [
            // Only one primary image can exist
            function ($validator) {
                $images = collect($this->input('images', []));
                if ($images->where('is_primary', true)->count() > 1) {
                    $validator->errors()->add('images', 'Only one primary image is allowed.');
                }
            }
        ];
    }
}
