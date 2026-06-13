<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('is-dispatcher') ?? false;
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'exists:routes,id'],
            'plane_id' => ['nullable', 'exists:planes,id'],
            'expected_takeoff' => ['required', 'date', 'after:now'],
            'expected_arrival' => ['required', 'date', 'after:expected_takeoff'],
            'status' => ['nullable', Rule::in(['scheduled', 'boarding', 'before_takeoff', 'in_flight', 'landed', 'delayed', 'cancelled', 'emergency_landing'])],
        ];
    }
}
