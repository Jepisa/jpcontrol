<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\SlackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTicketToSlackJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Ticket $ticket,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SlackService $slackService): void
    {
        $slackService->sendTicketNotification($this->ticket);
    }
}
