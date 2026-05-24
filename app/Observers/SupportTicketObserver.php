<?php

namespace App\Observers;

use App\Models\EmailQueue;
use App\Models\SupportTicket;
use App\Models\SupportTicketChangeLog;
use App\Notifications\TicketStatusChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupportTicketObserver
{
    /**
     * Fields whose changes are recorded in the change log.
     * Other dirty attributes (timestamps, internal state) are ignored.
     */
    private const TRACKED_FIELDS = [
        'status',
        'priority',
        'category_id',
        'description',
        'outcome',
        'closed_at',
    ];

    public function created(SupportTicket $ticket): void
    {
        SupportTicketChangeLog::create([
            'support_ticket_id' => $ticket->id,
            'changed_by_user_id' => Auth::id(),
            'field' => '__created__',
            'old_value' => null,
            'new_value' => 'open',
        ]);
    }

    public function updated(SupportTicket $ticket): void
    {
        $changes = $ticket->getChanges();
        $original = $ticket->getOriginal();
        $actor = Auth::id();
        $statusChanged = false;
        $statusFrom = null;
        $statusTo = null;

        DB::transaction(function () use ($ticket, $changes, $original, $actor, &$statusChanged, &$statusFrom, &$statusTo) {
            foreach ($changes as $field => $newValue) {
                if (! in_array($field, self::TRACKED_FIELDS, true)) {
                    continue;
                }

                $oldValue = $original[$field] ?? null;

                SupportTicketChangeLog::create([
                    'support_ticket_id' => $ticket->id,
                    'changed_by_user_id' => $actor,
                    'field' => $field,
                    'old_value' => $oldValue === null ? null : (string) $oldValue,
                    'new_value' => $newValue === null ? null : (string) $newValue,
                ]);

                if ($field === 'status') {
                    $statusChanged = true;
                    $statusFrom = $oldValue === null ? null : (string) $oldValue;
                    $statusTo = (string) $newValue;
                }
            }
        });

        if (! $statusChanged) {
            return;
        }

        $owner = $ticket->user()->first();

        if (! $owner) {
            return;
        }

        $statusTo = (string) $statusTo;
        $owner->notify(new TicketStatusChanged($ticket, $statusFrom, $statusTo));

        $name = $owner->first_name ?? $owner->name ?? null;

        EmailQueue::create([
            'recipient_email' => (string) $owner->email,
            'subject' => 'SkyAir — promena statusa za tiket #' . $ticket->number,
            'body' => $this->buildEmailBody($ticket, $statusFrom, $statusTo, $name === null ? null : (string) $name),
        ]);
    }

    private function buildEmailBody(SupportTicket $ticket, ?string $from, string $to, ?string $recipientName): string
    {
        $lines = [];
        $lines[] = 'Poštovani ' . ($recipientName ?: 'korisniče') . ',';
        $lines[] = '';
        $lines[] = "Status vašeg tiketa #{$ticket->number} je ažuriran.";
        $lines[] = '';
        $lines[] = 'Tiket: ' . $ticket->number;
        $lines[] = 'Stari status: ' . SupportTicket::statusLabel($from);
        $lines[] = 'Novi status: ' . SupportTicket::statusLabel($to);

        if ($ticket->category) {
            $lines[] = 'Kategorija: ' . $ticket->category->name;
        }

        $lines[] = 'Datum: ' . now()->toDateTimeString();
        $lines[] = '';
        $lines[] = 'Možete pratiti tiket na: ' . url('/support-tickets');
        $lines[] = '';
        $lines[] = 'Hvala što koristite SkyAir.';

        return implode("\n", $lines);
    }
}