<?php

namespace App\Http\Requests\Customer;

use App\Enums\FulfillmentType;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $delivery = $this->input('fulfillment_type') === FulfillmentType::Delivery->value;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'address.street' => [$delivery && ! $this->filled('address_id') ? 'required' : 'nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.district' => ['nullable', 'string', 'max:100'],
            'address.landmark' => ['nullable', 'string', 'max:255'],
            'address.label' => ['nullable', 'string', 'max:50'],
        ];
    }
}
