<?php

namespace App\Console\Commands;

use App\Models\EmailQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FlushEmailQueue extends Command
{
    protected $signature = 'email-queue:flush
        {--limit=50 : Maximum number of pending emails to process in one run}
        {--max-attempts=5 : Mark email as failed after this many attempts}';

    protected $description = 'Send all pending emails from the email_queue table';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $maxAttempts = (int) $this->option('max-attempts');

        $pending = EmailQueue::query()
            ->whereNull('sent_at')
            ->whereNull('failed_at')
            ->where('attempts', '<', $maxAttempts)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Email queue is empty.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($pending as $email) {
            $email->attempts = $email->attempts + 1;

            try {
                Mail::raw($email->body, function ($message) use ($email) {
                    $message->to($email->recipient_email)
                        ->subject($email->subject);
                });

                $email->sent_at = now();
                $email->last_error = null;
                $email->save();
                $sent++;
            } catch (Throwable $e) {
                $email->last_error = $e->getMessage();

                if ($email->attempts >= $maxAttempts) {
                    $email->failed_at = now();
                    $failed++;
                }

                $email->save();
                $this->warn("Email #{$email->id} attempt {$email->attempts} failed: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$pending->count()} email(s): {$sent} sent, {$failed} permanently failed.");

        return self::SUCCESS;
    }
}
