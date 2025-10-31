<?php

namespace App\Http\Requests\API\V1\ReturnRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'order_id' => 'required|integer|exists:orders,id',
            'reason' => 'nullable|string|max:1000',
            'order_items' => 'required|array|min:1',
            'order_items.*.id' => 'required|integer|exists:order_items,id',
            'order_items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
