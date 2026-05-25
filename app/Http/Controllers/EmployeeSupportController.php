<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketWorkLog;
use App\Models\Zaposlen;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeSupportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureEmployee($request);

        $tickets = SupportTicket::query()
            ->with([
                'user:id,first_name,last_name',
                'category:id,name',
                'workLogs.employee.user:id,first_name,last_name',
            ])
            ->latest('created_at')
            ->get()
            ->map(fn (SupportTicket $t) => $this->serializeTicket($t));

        $colleagues = Zaposlen::query()
            ->with('user:id,first_name,last_name')
            ->withCount(['supportWorkLogs as open_tickets_count' => function ($q) {
                $q->whereNull('ended_at');
            }])
            ->get()
            ->map(function (Zaposlen $z) {
                $u = $z->user;
                $name = trim(($u?->first_name ?? '').' '.($u?->last_name ?? '')) ?: 'Zaposleni';

                return [
                    'id' => $z->user_id,
                    'name' => $name,
                    'role' => $z->role,
                    'open_tickets' => (int) ($z->open_tickets_count ?? 0),
                ];
            })
            ->values();

        $me = $request->user();
        $myZaposlen = $me?->zaposlen;
        $myOpenCount = $myZaposlen
            ? SupportTicketWorkLog::where('employee_id', $myZaposlen->user_id)->whereNull('ended_at')->count()
            : 0;

        return Inertia::render('zaposleni/podrska', [
            'tickets' => $tickets,
            'colleagues' => $colleagues,
            'me' => [
                'id' => $me?->id,
                'employee_id' => $myZaposlen?->user_id,
                'name' => trim(($me->first_name ?? '').' '.($me->last_name ?? '')),
                'open_tickets' => $myOpenCount,
                'capacity' => 5,
            ],
        ]);
    }

    public function takeOver(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $employeeId = $this->ensureEmployee($request);

        DB::transaction(function () use ($ticket, $employeeId) {
            $this->closeOpenLogs($ticket, 'taken_over');

            SupportTicketWorkLog::create([
                'support_ticket_id' => $ticket->id,
                'employee_id' => $employeeId,
                'started_at' => now(),
                'action' => 'taken_over',
            ]);

            $ticket->status = 'in_progress';
            $ticket->save();
        });

        return back()->with('success', 'Tiket preuzet.');
    }

    public function requestInfo(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->ensureEmployee($request);
        $note = $request->validate(['note' => ['nullable', 'string', 'max:2000']])['note'] ?? null;

        DB::transaction(function () use ($ticket, $note) {
            $this->closeOpenLogs($ticket, 'requested_info', $note);
            $ticket->status = 'requires_info';
            $ticket->save();
        });

        return back()->with('success', 'Tiket prebačen u "Zahteva info".');
    }

    public function transfer(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->ensureEmployee($request);

        $data = $request->validate([
            'to_employee_id' => ['required', 'integer', 'exists:zaposleni,user_id'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($ticket, $data) {
            $this->closeOpenLogs($ticket, 'transferred', $data['note'] ?? null);

            SupportTicketWorkLog::create([
                'support_ticket_id' => $ticket->id,
                'employee_id' => $data['to_employee_id'],
                'started_at' => now(),
                'action' => 'received_transfer',
                'note' => $data['note'] ?? null,
            ]);

            $ticket->status = 'transferred';
            $ticket->save();
        });

        return back()->with('success', 'Tiket prosleđen.');
    }

    public function complete(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $employeeId = $this->ensureEmployee($request);

        $data = $request->validate([
            'outcome' => ['required', Rule::in(['success', 'partial', 'fail'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($ticket, $data, $employeeId) {
            $action = 'closed_'.$data['outcome'];
            $note = $data['note'] ?? null;

            $hasOpenLog = SupportTicketWorkLog::where('support_ticket_id', $ticket->id)
                ->whereNull('ended_at')
                ->exists();

            if ($hasOpenLog) {
                $this->closeOpenLogs($ticket, $action, $note);
            } else {
                $now = Carbon::now();
                SupportTicketWorkLog::create([
                    'support_ticket_id' => $ticket->id,
                    'employee_id' => $employeeId,
                    'started_at' => $now,
                    'ended_at' => $now,
                    'action' => $action,
                    'note' => $note,
                ]);
            }

            $ticket->status = 'closed';
            $ticket->outcome = $data['outcome'];
            $ticket->closed_at = now();
            $ticket->save();
        });

        return back()->with('success', 'Tiket zatvoren.');
    }

    private function closeOpenLogs(SupportTicket $ticket, string $action, ?string $note = null): void
    {
        SupportTicketWorkLog::where('support_ticket_id', $ticket->id)
            ->whereNull('ended_at')
            ->update([
                'ended_at' => Carbon::now(),
                'action' => $action,
                'note' => $note,
                'updated_at' => Carbon::now(),
            ]);
    }

    private function ensureEmployee(Request $request): int
    {
        $user = $request->user();
        $zaposlen = $user?->zaposlen;

        if (! $zaposlen) {
            throw new AuthorizationException('Samo zaposleni mogu pristupiti.');
        }

        return $zaposlen->user_id;
    }

    private function serializeTicket(SupportTicket $ticket): array
    {
        $customer = $ticket->user;
        $customerName = 'Korisnik';
        if ($customer) {
            $full = trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));
            $customerName = $full !== '' ? $full : ($customer->name ?? 'Korisnik');
        }

        $workLogs = $ticket->workLogs->map(function ($log) {
            $u = $log->employee?->user;
            $name = $u
                ? trim(($u->first_name ?? '').' '.($u->last_name ?? ''))
                : null;

            return [
                'id' => $log->id,
                'employee_id' => $log->employee_id,
                'employee_name' => $name ?: 'Zaposleni',
                'started_at' => $log->started_at?->toIso8601String(),
                'ended_at' => $log->ended_at?->toIso8601String(),
                'action' => $log->action,
                'note' => $log->note,
            ];
        })->values();

        $currentOwner = $workLogs->firstWhere('ended_at', null);

        return [
            'id' => $ticket->id,
            'number' => $ticket->number,
            'category' => $ticket->category?->name,
            'description' => $ticket->description,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'outcome' => $ticket->outcome,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'closed_at' => $ticket->closed_at?->toIso8601String(),
            'customer_name' => $customerName,
            'current_owner' => $currentOwner ? [
                'employee_id' => $currentOwner['employee_id'],
                'name' => $currentOwner['employee_name'],
                'started_at' => $currentOwner['started_at'],
            ] : null,
            'work_logs' => $workLogs,
        ];
    }
}
