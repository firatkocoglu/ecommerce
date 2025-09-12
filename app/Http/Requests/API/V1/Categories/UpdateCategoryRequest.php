<?php

/**
 * HINTS FOR IDE (VS Code / Intelephense):
 * These annotations let the IDE know that FormRequest carries Request methods.
 *
 * @mixin \Illuminate\Http\Request
 *
 * @method bool filled(string $key)
 * @method mixed input(string $key, $default = null)
 * @method mixed route(string|null $key = null, mixed $default = null)
 */

namespace App\Http\Requests\API\V1\Categories;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:categories,slug,'.$id],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    public function after(): array
    {
        return [
            // Avoid self parenting category
            function ($validator) {
                $id = (int) $this->route('id');
                if ($this->filled('parent_id')) {
                    $parentId = (int) $this->input('parent_id');
                    if ($parentId === $id) {
                        $validator->errors()->add('parent_id', 'The parent category cannot be the same as the current category.');
                    }
                }
            },
        ];
    }
}
