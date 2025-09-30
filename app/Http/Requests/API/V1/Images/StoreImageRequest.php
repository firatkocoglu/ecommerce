<?php

namespace App\Http\Requests\API\V1\Images;

use App\Enums\ImageStatus;
use App\Http\Requests\Concerns\HasImageAfterHooks;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreImageRequest extends FormRequest
{
    use HasImageAfterHooks;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'], // Max 5MB
            'is_primary' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in([ImageStatus::PROCESSING, ImageStatus::COMPLETED, ImageStatus::FAILED])],
        ];
    }

    public function withValidator($validator): void
    {
        $this->applySinglePrimaryImageRule($validator);
    }
}
