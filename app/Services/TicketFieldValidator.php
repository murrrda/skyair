<?php

namespace App\Services;

use App\Models\CategoryField;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TicketFieldValidator
{
    /**
     * @return array{results: array<string, array{status: string, reason: ?string, display_label: string, prompt: string, required: bool, value: ?string, field_type: string}>, all_valid: bool}
     */
    public function validate(SupportTicket $ticket): array
    {
        $ticket->loadMissing('category.fields', 'fieldValues.categoryField');

        $results = [];
        $allValid = true;

        foreach ($ticket->category->fields as $field) {
            $fieldValue = $ticket->fieldValues->firstWhere('category_field_id', $field->id);
            $value = $fieldValue?->value;

            $base = [
                'display_label' => $field->display_label,
                'prompt' => $field->prompt,
                'required' => $field->required,
                'value' => $value,
                'field_type' => $field->field_type,
            ];

            if ($value === null || trim($value) === '') {
                $results[$field->field_name] = array_merge($base, [
                    'status' => $field->required ? 'missing' : 'valid',
                    'reason' => $field->required ? 'Obavezno polje nije popunjeno.' : null,
                    'value' => null,
                ]);

                if ($field->required) {
                    $allValid = false;
                }

                continue;
            }

            $result = $this->validateField($field, $value, $ticket->user_id);
            $results[$field->field_name] = array_merge($base, $result);

            if ($result['status'] !== 'valid') {
                $allValid = ($field->required) ? false : $allValid;
            }
        }

        return ['results' => $results, 'all_valid' => $allValid];
    }

    /**
     * @return array{status: string, reason: ?string}
     */
    private function validateField(CategoryField $field, string $value, int $userId): array
    {
        if ($field->validation_rule) {
            if (! @preg_match($field->validation_rule, $value)) {
                return [
                    'status' => 'invalid',
                    'reason' => "Vrednost \"{$value}\" ne odgovara očekivanom formatu.",
                ];
            }
        }

        if ($field->field_type === 'datetime') {
            try {
                Carbon::parse($value);
            } catch (\Exception) {
                return [
                    'status' => 'invalid',
                    'reason' => 'Neispravan format datuma.',
                ];
            }
        }

        if ($field->field_type === 'number') {
            if (! is_numeric($value)) {
                return [
                    'status' => 'invalid',
                    'reason' => 'Vrednost mora biti broj.',
                ];
            }
        }

        if ($field->reference_table && $field->reference_column) {
            return $this->validateReference($field, $value, $userId);
        }

        return ['status' => 'valid', 'reason' => null];
    }

    /**
     * @return array{status: string, reason: ?string}
     */
    private function validateReference(CategoryField $field, string $value, int $userId): array
    {
        $record = DB::table($field->reference_table)
            ->where('number', $value)
            ->first();

        if (! $record && is_numeric($value)) {
            $record = DB::table($field->reference_table)
                ->where($field->reference_column, $value)
                ->first();
        }

        if (! $record) {
            return [
                'status' => 'invalid',
                'reason' => "Referenca \"{$value}\" ne postoji u sistemu.",
            ];
        }

        if ($field->reference_table === 'flights') {
            $hasReservation = DB::table('flight_tickets')
                ->join('reservations', 'flight_tickets.reservation_id', '=', 'reservations.id')
                ->where('flight_tickets.flight_id', $record->id)
                ->where('reservations.user_id', $userId)
                ->exists();

            if (! $hasReservation) {
                return [
                    'status' => 'invalid',
                    'reason' => 'Nemate rezervaciju na navedenom letu.',
                ];
            }
        }

        return ['status' => 'valid', 'reason' => null];
    }
}
