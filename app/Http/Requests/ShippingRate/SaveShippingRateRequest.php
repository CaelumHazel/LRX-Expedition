<?php

namespace App\Http\Requests\ShippingRate;

use Illuminate\Foundation\Http\FormRequest;

class SaveShippingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'origin_city' => ['required', 'string', 'max:100'],
            'destination_city' => ['required', 'string', 'max:100', 'different:origin_city'],
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'cargo_type' => ['required', 'string', 'max:100'],
            'price_per_kg' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'estimated_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
