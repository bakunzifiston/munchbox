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
        $customerId = $this->user()?->customer?->id;

        return [
            'checkout_token' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:9', 'max:30', 'regex:/^\+?[0-9\s()-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'address_id' => $customerId
                ? ['nullable', 'integer', Rule::exists('addresses', 'id')->where('customer_id', $customerId)]
                : ['prohibited'],
            'delivery_area' => [
                Rule::requiredIf($delivery && blank($this->input('address_id'))),
                'nullable',
                'string',
                Rule::in(self::areaTitles()),
            ],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.landmark' => ['nullable', 'string', 'max:255'],
            'address.label' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'checkout_token.required' => 'Your checkout page expired. Please review your order and try again.',
            'phone.required' => 'Please enter your phone number before placing the order.',
            'phone.min' => 'Please enter a valid phone number.',
            'phone.regex' => 'Please enter a valid phone number (digits only, with optional +).',
            'payment_method.required' => 'Please choose a payment method.',
            'fulfillment_type.required' => 'Please choose delivery or pickup.',
            'delivery_area.required' => 'Please choose your delivery area.',
            'delivery_area.in' => 'We don’t deliver to that area yet. Please choose one from the list or select pickup.',
            'address_id.exists' => 'Please choose one of your saved addresses.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function areaTitles(): array
    {
        return array_column(config('menu.delivery_areas', []), 'title');
    }

    protected function prepareForValidation(): void
    {
        $address = $this->input('address', []);
        $address = is_array($address) ? $address : [];

        $this->merge([
            'phone' => trim((string) $this->input('phone', '')),
            'address_id' => $this->input('address_id') ?: null,
            'delivery_area' => trim((string) $this->input('delivery_area', '')) ?: null,
            'address' => array_merge($address, [
                'street' => trim((string) data_get($address, 'street', '')) ?: null,
                'landmark' => trim((string) data_get($address, 'landmark', '')) ?: null,
            ]),
        ]);
    }
}
