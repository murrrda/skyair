<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('is-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starting_airport_id' => ['required', 'integer', 'exists:airports,id'],
            'landing_airport_id' => ['required', 'integer', 'exists:airports,id', 'different:starting_airport_id'],
            'distance_km' => ['required', 'integer', 'min:1'],
            'estimated_time' => ['required', 'integer', 'min:1'],
            'active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'landing_airport_id.different' => 'Krajnji aerodrom mora biti različit od polaznog.',
        ];
    }
}
