<?php

namespace App\Http\Requests\API\V1\ReturnRequests;

use Illuminate\Foundation\Http\FormRequest;

class RejectReturnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:1000',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
