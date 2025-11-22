<?php

namespace App\Http\Requests\API\V1\ImageIngestion;

use Illuminate\Foundation\Http\FormRequest;

class ImageIngestionRequest extends FormRequest
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
        return [
            'imageData' => ['required', 'array', 'min:1'],

            'imageData.*.secure_url' => ['required', 'string'],
            'imageData.*.public_id' => ['required', 'string'],
            'imageData.*.width' => ['required', 'integer'],
            'imageData.*.height' => ['required', 'integer'],
            'imageData.*.format' => ['required', 'string'],
            'imageData.*.bytes' => ['required', 'integer'],
        ];
    }
}
