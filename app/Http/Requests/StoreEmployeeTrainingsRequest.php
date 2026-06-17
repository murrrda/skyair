<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEmployeeTrainingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('is-admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'trainings' => ['present', 'array'],
            'trainings.*.id' => ['nullable', 'integer'],
            'trainings.*.training_type_id' => ['required', 'integer', 'exists:training_types,id'],
            'trainings.*.started_at' => ['required', 'date'],
            'trainings.*.finished_at' => ['required', 'date'],
            'trainings.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('trainings', []) as $index => $training) {
                $startedAt = $training['started_at'] ?? null;
                $finishedAt = $training['finished_at'] ?? null;

                if ($startedAt && $finishedAt && strtotime($finishedAt) < strtotime($startedAt)) {
                    $validator->errors()->add(
                        "trainings.{$index}.finished_at",
                        'Datum završetka ne može biti prije datuma početka.'
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'trainings.*.training_type_id' => 'tip obuke',
            'trainings.*.started_at' => 'datum početka',
            'trainings.*.finished_at' => 'datum završetka',
            'trainings.*.note' => 'napomena',
        ];
    }
}
