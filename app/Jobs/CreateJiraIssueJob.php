<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\JiraService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateJiraIssueJob implements ShouldQueue
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
    public function handle(JiraService $jiraService): void
    {
        $jiraService->createIssue($this->ticket);
    }
}
