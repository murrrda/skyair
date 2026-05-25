<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution_speed' => ['required', 'integer', 'between:1,5'],
            'resolution_speed_comment' => ['nullable', 'string', 'max:500'],
            'communication_quality' => ['nullable', 'integer', 'between:1,5'],
            'communication_quality_comment' => ['nullable', 'string', 'max:500'],
            'degree_of_resolution' => ['required', 'integer', 'between:1,5'],
            'degree_of_resolution_comment' => ['nullable', 'string', 'max:500'],
            'agents' => ['present', 'array'],
            'agents.*.employee_id' => ['required', 'integer', 'exists:zaposleni,user_id'],
            'agents.*.rating' => ['required', 'integer', 'between:1,5'],
            'agents.*.comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
