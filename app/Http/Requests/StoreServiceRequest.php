<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('is-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'started' => ['required', 'date'],
            'ended' => ['nullable', 'date', 'after_or_equal:started'],
            'status' => ['required', Rule::in(['pending', 'in_progress', 'finished'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'service_center' => ['required', 'string', 'max:255'],
        ];
    }
}
