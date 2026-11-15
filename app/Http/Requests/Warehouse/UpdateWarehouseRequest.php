<?php

namespace App\Http\Requests\Warehouse;

use App\Support\IndonesianCityCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouse = $this->route('warehouse');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('warehouses', 'code')
                    ->ignore($warehouse->id),
            ],
            'address' => ['required', 'string'],
            'city' => ['required', Rule::in(app(IndonesianCityCatalog::class)->names())],
            'is_active' => ['boolean'],
        ];
    }
}