<?php

namespace App\Http\Controllers;

use App\Events\NewSupportTicketCreated;
use App\Http\Requests\StoreSupportTicketRatingRequest;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\Category;
use App\Models\SupportTicket;
use App\Models\SupportTicketRating;
use App\Models\SupportTicketRatingAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
                'workLogs.employee.user:id,first_name,last_name,name',
                'rating.agentRatings',
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

        $ticket = new SupportTicket;
        $ticket->description = $validated['description'];
        $ticket->category_id = $validated['category_id'];
        $ticket->priority = $validated['priority'] ?? 'medium';
        $ticket->number = 'ST-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
        $ticket->status = 'open';
        $request->user()->supportTickets()->save($ticket);

        $ticket->load('category', 'user');
        NewSupportTicketCreated::dispatch($ticket);

        return back()->with('success', 'Support ticket submitted successfully.');
    }

    public function rate(StoreSupportTicketRatingRequest $request, SupportTicket $ticket): RedirectResponse
    {
        if ($ticket->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($ticket->status !== 'closed') {
            throw ValidationException::withMessages([
                'ticket' => 'Recenzija je moguća tek kada je tiket završen.',
            ]);
        }

        $ticket->load('rating', 'workLogs');

        if ($ticket->rating) {
            throw ValidationException::withMessages([
                'ticket' => 'Tiket je već ocenjen.',
            ]);
        }

        $validated = $request->validated();

        $allowedAgents = $ticket->workLogs
            ->pluck('employee_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $submittedAgents = collect($validated['agents'])->pluck('employee_id')->map(fn ($id) => (int) $id);

        if ($submittedAgents->diff($allowedAgents)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'agents' => 'Ocenjeni agent nije radio na ovom tiketu.',
            ]);
        }

        if ($submittedAgents->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'agents' => 'Svaki agent može biti ocenjen samo jednom.',
            ]);
        }

        $hasAdditionalContact = $ticket->workLogs->contains(fn ($log) => $log->action === 'requested_info');

        $communication = $hasAdditionalContact ? ($validated['communication_quality'] ?? null) : null;
        $degree = $validated['degree_of_resolution'] ?? null;

        if ($hasAdditionalContact && $communication === null) {
            throw ValidationException::withMessages([
                'communication_quality' => 'Ocena komunikacije je obavezna.',
            ]);
        }

        if ($degree === null) {
            throw ValidationException::withMessages([
                'degree_of_resolution' => 'Ocena stepena rešenja je obavezna.',
            ]);
        }

        DB::transaction(function () use ($ticket, $validated, $communication, $degree, $hasAdditionalContact) {
            $rating = SupportTicketRating::create([
                'support_ticket_id' => $ticket->id,
                'resolution_speed' => $validated['resolution_speed'],
                'resolution_speed_comment' => $this->normalizeComment($validated['resolution_speed_comment'] ?? null),
                'communication_quality' => $communication,
                'communication_quality_comment' => $hasAdditionalContact
                    ? $this->normalizeComment($validated['communication_quality_comment'] ?? null)
                    : null,
                'degree_of_resolution' => $degree,
                'degree_of_resolution_comment' => $this->normalizeComment($validated['degree_of_resolution_comment'] ?? null),
            ]);

            foreach ($validated['agents'] as $agent) {
                SupportTicketRatingAgent::create([
                    'support_ticket_rating_id' => $rating->id,
                    'employee_id' => $agent['employee_id'],
                    'rating' => $agent['rating'],
                    'comment' => $this->normalizeComment($agent['comment'] ?? null),
                ]);
            }
        });

        return back()->with('success', 'Hvala na oceni!');
    }

    private function normalizeComment(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function serializeTicket(SupportTicket $ticket): array
    {
        $agents = $ticket->workLogs
            ->filter(fn ($log) => $log->employee_id !== null)
            ->groupBy('employee_id')
            ->map(function ($logs) {
                $first = $logs->first();
                $user = $first->employee?->user;
                $full = $user
                    ? trim(($user->first_name ?? '').' '.($user->last_name ?? ''))
                    : '';
                $name = $full !== '' ? $full : ($user?->name ?? 'Zaposleni');

                return [
                    'employee_id' => (int) $first->employee_id,
                    'name' => $name,
                ];
            })
            ->values();

        $hasAdditionalContact = $ticket->workLogs->contains(fn ($log) => $log->action === 'requested_info');

        $rating = null;
        if ($ticket->rating) {
            $rating = [
                'resolution_speed' => $ticket->rating->resolution_speed,
                'resolution_speed_comment' => $ticket->rating->resolution_speed_comment,
                'communication_quality' => $ticket->rating->communication_quality,
                'communication_quality_comment' => $ticket->rating->communication_quality_comment,
                'degree_of_resolution' => $ticket->rating->degree_of_resolution,
                'degree_of_resolution_comment' => $ticket->rating->degree_of_resolution_comment,
                'created_at' => $ticket->rating->created_at?->toIso8601String(),
                'agents' => $ticket->rating->agentRatings->map(fn ($a) => [
                    'employee_id' => (int) $a->employee_id,
                    'rating' => (int) $a->rating,
                    'comment' => $a->comment,
                ])->values(),
            ];
        }

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
                $full = $user
                    ? trim(($user->first_name ?? '').' '.($user->last_name ?? ''))
                    : '';
                $name = $full !== '' ? $full : ($user?->name ?? 'Zaposleni');

                return [
                    'id' => $log->id,
                    'employee_id' => $log->employee_id,
                    'employee_name' => $name,
                    'started_at' => $log->started_at?->toIso8601String(),
                    'ended_at' => $log->ended_at?->toIso8601String(),
                    'action' => $log->action,
                    'note' => $log->note,
                ];
            })->values(),
            'agents' => $agents,
            'rating_context' => [
                'has_additional_contact' => $hasAdditionalContact,
            ],
            'rating' => $rating,
        ];
    }
}
