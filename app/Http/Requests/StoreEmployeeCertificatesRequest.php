<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEmployeeCertificatesRequest extends FormRequest
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
            'certificates' => ['present', 'array'],
            'certificates.*.id' => ['nullable', 'integer'],
            'certificates.*.certificate_type_id' => ['required', 'integer', 'exists:certificate_types,id'],
            'certificates.*.issued_at' => ['required', 'date'],
            'certificates.*.expires_at' => ['required', 'date'],
            'certificates.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('certificates', []) as $index => $certificate) {
                $issuedAt = $certificate['issued_at'] ?? null;
                $expiresAt = $certificate['expires_at'] ?? null;

                if ($issuedAt && $expiresAt && strtotime($expiresAt) <= strtotime($issuedAt)) {
                    $validator->errors()->add(
                        "certificates.{$index}.expires_at",
                        'Datum isteka mora biti nakon datuma izdavanja.'
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
            'certificates.*.certificate_type_id' => 'tip sertifikata',
            'certificates.*.issued_at' => 'datum izdavanja',
            'certificates.*.expires_at' => 'datum isteka',
            'certificates.*.note' => 'napomena',
        ];
    }
}
