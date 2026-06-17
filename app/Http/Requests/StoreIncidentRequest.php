<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('is-admin');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'flight_id' => ['required', 'integer', 'exists:flights,id'],
            'incident_type_id' => ['required', 'integer', 'exists:incident_types,id'],
            'severity_level_id' => ['required', 'integer', 'exists:severity_levels,id'],
            'occurred_at' => ['required', 'date'],
            'description' => ['required', 'string', 'max:5000'],
            'responsible_employees' => ['nullable', 'array'],
            'responsible_employees.*' => ['integer', 'exists:zaposleni,user_id'],
        ];
    }
}
