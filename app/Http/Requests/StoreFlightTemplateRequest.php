<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlightTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('is-dispatcher') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'route_id' => ['required', 'exists:routes,id'],
            'departure_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'min_capacity' => ['nullable', 'integer', 'min:1'],
            'luxury_level' => ['nullable', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
