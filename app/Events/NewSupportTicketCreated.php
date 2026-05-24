<?php

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewSupportTicketCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SupportTicket $ticket) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('support.queue')];
    }

    public function broadcastAs(): string
    {
        return 'ticket.created';
    }

    public function broadcastWith(): array
    {
        $customer = $this->ticket->user;
        $customerName = 'Korisnik';

        if ($customer) {
            $full = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $customerName = $full !== '' ? $full : ($customer->name ?? 'Korisnik');
        }

        return [
            'id' => $this->ticket->id,
            'number' => $this->ticket->number,
            'category' => $this->ticket->category?->name,
            'priority' => $this->ticket->priority,
            'status' => $this->ticket->status,
            'customer_name' => $customerName,
            'description' => $this->ticket->description,
            'created_at' => $this->ticket->created_at?->toIso8601String(),
        ];
    }
}