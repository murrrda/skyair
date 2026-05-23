<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:category,id'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
        ];
    }
}