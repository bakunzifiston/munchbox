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
            'phone' => ['required', 'string', 'min:9', 'max:30', 'regex:/^\+?[0-9\s()-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.district' => ['nullable', 'string', 'max:100'],
            'address.landmark' => ['nullable', 'string', 'max:255'],
            'address.label' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Please enter your phone number before placing the order.',
            'phone.min' => 'Please enter a valid phone number.',
            'phone.regex' => 'Please enter a valid phone number (digits only, with optional +).',
            'payment_method.required' => 'Please choose a payment method.',
            'fulfillment_type.required' => 'Please choose delivery or pickup.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = trim((string) $this->input('phone', ''));
        $address = $this->input('address', []);

        $this->merge([
            'phone' => $phone,
            'address' => array_merge(is_array($address) ? $address : [], [
                'street' => trim((string) data_get($address, 'street', '')) ?: null,
                'city' => trim((string) data_get($address, 'city', '')) ?: 'Kigali',
                'district' => trim((string) data_get($address, 'district', '')) ?: null,
                'landmark' => trim((string) data_get($address, 'landmark', '')) ?: null,
            ]),
        ]);
    }
}
