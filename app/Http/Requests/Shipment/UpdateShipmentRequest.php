<?php

namespace App\Http\Requests\Shipment;

use App\Support\IndonesianCityCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sender_id' => ['nullable', 'required_without:sender_name', 'exists:customers,id'],
            'sender_name' => ['required_without:sender_id', 'string', 'max:255'],
            'sender_phone' => ['required_without:sender_id', 'string', 'max:20', 'regex:/^[0-9+()\\s-]{8,20}$/'],
            'receiver_id' => ['nullable', 'required_without:receiver_name', 'exists:customers,id'],
            'receiver_name' => ['required_without:receiver_id', 'string', 'max:255'],
            'receiver_phone' => ['required_without:receiver_id', 'string', 'max:20', 'regex:/^[0-9+()\\s-]{8,20}$/'],
            'courier_id' => ['nullable', 'exists:couriers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'cargo_type' => ['required', 'string', 'max:100'],
            'origin_city' => ['required', Rule::in(app(IndonesianCityCatalog::class)->names())],
            'destination_city' => ['required', Rule::in(app(IndonesianCityCatalog::class)->names())],
            'receiver_address' => ['required', 'string', 'max:2000'],
            'total_weight' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'declared_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'packages' => ['sometimes', 'array'],
            'packages.*.name' => ['nullable', 'string', 'max:255'],
            'packages.*.description' => ['nullable', 'string', 'max:2000'],
            'packages.*.quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ((array) $this->input('packages', []) as $index => $package) {
                if (! is_array($package)) {
                    continue;
                }

                if (filled($package['name'] ?? null) && empty($package['quantity'])) {
                    $validator->errors()->add("packages.{$index}.quantity", 'Masukkan jumlah paket.');
                }
            }
        });
    }
}
