<?php

namespace App\Jobs;

use App\Models\SupportTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupDraftTickets implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        SupportTicket::whereIn('status', ['draft', 'abandoned'])
            ->where('created_at', '<', now()->subHours(24))
            ->delete();
    }
}
