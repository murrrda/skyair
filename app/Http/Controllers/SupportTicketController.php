<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\Category;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = $request->user()
            ->supportTickets()
            ->with([
                'category:id,name',
                'workLogs.employee.user:id,first_name,last_name',
            ])
            ->latest('created_at')
            ->get()
            ->map(fn (SupportTicket $ticket) => $this->serializeTicket($ticket));

        $categories = Category::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name]);

        return Inertia::render('kupac/moji-tiketi', [
            'tickets' => $tickets,
            'categories' => $categories,
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $seq = DB::selectOne("SELECT nextval('support_ticket_number_seq') AS val")->val;

        $ticket = new SupportTicket();
        $ticket->description = $validated['description'];
        $ticket->category_id = $validated['category_id'];
        $ticket->priority = $validated['priority'] ?? 'medium';
        $ticket->number = 'ST-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        $ticket->status = 'open';
        $request->user()->supportTickets()->save($ticket);

        return back()->with('success', 'Support ticket submitted successfully.');
    }

    private function serializeTicket(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'number' => $ticket->number,
            'category' => $ticket->category?->name,
            'category_id' => $ticket->category_id,
            'description' => $ticket->description,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'outcome' => $ticket->outcome,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'closed_at' => $ticket->closed_at?->toIso8601String(),
            'work_logs' => $ticket->workLogs->map(function ($log) {
                $user = $log->employee?->user;
                $name = $user
                    ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
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
            })->values(),
        ];
    }
}