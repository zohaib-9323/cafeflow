<?php

namespace App\Http\Requests;

use App\Enums\FulfillmentType;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Customer and delivery details. Prices are never accepted from the client. */
class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        $delivery = Rule::requiredIf(fn () => $this->input('fulfillment_type') === FulfillmentType::Delivery->value);

        return [
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'regex:/^[0-9 +()\-.]{6,32}$/'],
            'delivery_line1' => [$delivery, 'nullable', 'string', 'max:255'],
            'delivery_line2' => ['nullable', 'string', 'max:255'],
            'delivery_city' => [$delivery, 'nullable', 'string', 'max:100'],
            'delivery_postal_code' => [$delivery, 'nullable', 'string', 'max:20'],
            'delivery_instructions' => ['nullable', 'string', 'max:500'],
            'save_address' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'promo_code' => ['nullable', 'string', 'max:40', 'alpha_dash'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'name',
            'customer_phone' => 'phone number',
            'delivery_line1' => 'street address',
            'delivery_city' => 'city',
            'delivery_postal_code' => 'postal code',
        ];
    }

    public function messages(): array
    {
        return ['customer_phone.regex' => 'Please enter a valid phone number.'];
    }
}
