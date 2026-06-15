<?php

namespace App\Services;

use App\Models\EmployeeShift;
use App\Models\SupportTicket;
use App\Models\SupportTicketWorkLog;
use App\Models\TicketAssignmentLog;
use App\Models\Zaposlen;
use App\Notifications\TicketAutoAssigned;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TicketAutoAssigner
{
    public const MAX_OPEN_TICKETS = 5;

    public const SHIFT_END_BUFFER_MINUTES = 30;

    public function assign(SupportTicket $ticket): ?int
    {
        $now = Carbon::now();
        $today = $now->toDateString();

        $agents = Zaposlen::where('role', 'agent')
            ->where('status', 'aktivan')
            ->get();

        if ($agents->isEmpty()) {
            $this->logAssignment($ticket, null, [], 'no_agents', 'Nema aktivnih agenata.');

            return null;
        }

        $candidates = [];

        foreach ($agents as $agent) {
            $openTickets = SupportTicketWorkLog::where('employee_id', $agent->user_id)
                ->whereNull('ended_at')
                ->count();

            if ($openTickets >= self::MAX_OPEN_TICKETS) {
                continue;
            }

            $shift = EmployeeShift::where('employee_id', $agent->user_id)
                ->where('date', $today)
                ->first();

            if (! $shift) {
                continue;
            }

            $shiftEnd = Carbon::parse($today.' '.$shift->end_time->format('H:i:s'));
            $shiftStart = Carbon::parse($today.' '.$shift->start_time->format('H:i:s'));

            if ($now->lt($shiftStart) || $now->gte($shiftEnd)) {
                continue;
            }

            $remainingMinutes = $now->diffInMinutes($shiftEnd);
            $nearingEnd = $remainingMinutes <= self::SHIFT_END_BUFFER_MINUTES;

            $candidates[] = [
                'employee_id' => $agent->user_id,
                'open_tickets' => $openTickets,
                'remaining_minutes' => $remainingMinutes,
                'nearing_shift_end' => $nearingEnd,
            ];
        }

        if (empty($candidates)) {
            $this->logAssignment($ticket, null, [], 'no_eligible', 'Nema dostupnih agenata (svi su zauzeti ili van smene).');

            return null;
        }

        usort($candidates, function ($a, $b) {
            if ($a['nearing_shift_end'] !== $b['nearing_shift_end']) {
                return $a['nearing_shift_end'] <=> $b['nearing_shift_end'];
            }

            if ($a['open_tickets'] !== $b['open_tickets']) {
                return $a['open_tickets'] <=> $b['open_tickets'];
            }

            return $b['remaining_minutes'] <=> $a['remaining_minutes'];
        });

        $best = $candidates[0];

        DB::transaction(function () use ($ticket, $best) {
            SupportTicketWorkLog::create([
                'support_ticket_id' => $ticket->id,
                'employee_id' => $best['employee_id'],
                'started_at' => now(),
                'action' => 'auto_assigned',
            ]);

            $ticket->update(['status' => 'in_progress']);
        });

        $this->logAssignment($ticket, $best['employee_id'], $candidates, 'assigned', null);

        $assignedAgent = Zaposlen::with('user')->find($best['employee_id']);
        $assignedAgent?->user?->notify(new TicketAutoAssigned($ticket));

        return $best['employee_id'];
    }

    private function logAssignment(
        SupportTicket $ticket,
        ?int $employeeId,
        array $candidates,
        string $outcome,
        ?string $reason,
    ): void {
        TicketAssignmentLog::create([
            'support_ticket_id' => $ticket->id,
            'assigned_employee_id' => $employeeId,
            'candidate_scores' => ! empty($candidates) ? $candidates : null,
            'outcome' => $outcome,
            'reason' => $reason,
        ]);
    }
}
