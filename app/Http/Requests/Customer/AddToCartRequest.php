<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'options' => ['nullable', 'array'],
            'options.*' => ['integer', 'exists:product_options,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
