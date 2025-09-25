<?php

namespace App\Http\Requests\API\V1\Images;

use App\Http\Requests\Concerns\HasImageAfterHooks;
use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    use HasImageAfterHooks;

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
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'], // Max 5MB
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->applySinglePrimaryImageRule($validator);
    }
}
