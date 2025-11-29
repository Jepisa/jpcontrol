<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Jobs\CreateJiraIssueJob;
use App\Jobs\SendTicketToSlackJob;
use App\Services\MentionService;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function afterCreate(): void
    {
        SendTicketToSlackJob::dispatch($this->record);
        CreateJiraIssueJob::dispatch($this->record);

        // Notify mentioned users in description
        $mentionService = app(MentionService::class);
        $mentionService->notifyMentionedUsers(
            $this->record->description,
            $this->record,
            auth()->user(),
            'description'
        );
    }
}
