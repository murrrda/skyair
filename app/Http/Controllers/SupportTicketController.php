<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $seq = DB::selectOne("SELECT nextval('support_ticket_number_seq') AS val")->val;

        $ticket = new SupportTicket();
        $ticket->description = $validated['description'];
        $ticket->category_id = $validated['category_id'];
        $ticket->number = 'ST-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        $ticket->status = 'open';
        $request->user()->supportTickets()->save($ticket);

        return back()->with('success', 'Support ticket submitted successfully.');
    }
}