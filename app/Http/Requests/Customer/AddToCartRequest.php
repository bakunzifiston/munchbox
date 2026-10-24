<?php

namespace App\Http\Requests\Customer;

use App\Services\CartService;
use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $options = collect($this->input('options', []))
            ->flatten()
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->merge(['options' => $options]);
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_QUANTITY],
            'options' => ['nullable', 'array'],
            'options.*' => ['integer', 'exists:product_options,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
