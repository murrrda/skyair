<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('is-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'reg_number' => ['required', 'integer', 'min:1', 'unique:planes,reg_number'],
            'model' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'luxury_level' => ['required', 'integer', 'min:1', 'max:5'],
            'range_km' => ['required', 'integer', 'min:1'],
            'max_speed' => ['required', 'integer', 'min:1'],
            'repair_service_interval' => ['required', 'integer', 'min:1'],
            'model_year' => ['required', 'integer', 'min:1950', 'max:' . (date('Y') + 2)],
            'status' => ['nullable', Rule::in(['in_garage', 'in_flight', 'in_service'])],
            'commissioned_at' => ['nullable', 'date'],
            'total_mileage' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
