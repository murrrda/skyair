<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TicketAutoAssigned extends Notification implements ShouldBroadcast
{
    public function __construct(public SupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    private function payload(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'category' => $this->ticket->category?->name,
            'priority' => $this->ticket->priority,
            'message' => "Tiket #{$this->ticket->number} vam je automatski dodeljen.",
        ];
    }
}
