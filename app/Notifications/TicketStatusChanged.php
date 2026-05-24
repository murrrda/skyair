<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChanged extends Notification implements ShouldBroadcast
{
    public function __construct(
        public SupportTicket $ticket,
        public ?string $statusFrom,
        public string $statusTo,
    ) {}

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
        $statusToLabel = SupportTicket::statusLabel($this->statusTo);
        $statusFromLabel = SupportTicket::statusLabel($this->statusFrom);

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'status_from' => $this->statusFrom,
            'status_to' => $this->statusTo,
            'status_from_label' => $statusFromLabel,
            'status_to_label' => $statusToLabel,
            'message' => "Tiket #{$this->ticket->number}: status promenjen u \"{$statusToLabel}\".",
        ];
    }
}
