<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Jobs\CreateJiraIssueJob;
use App\Jobs\SendTicketToSlackJob;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function afterCreate(): void
    {
        SendTicketToSlackJob::dispatch($this->record);
        CreateJiraIssueJob::dispatch($this->record);
    }
}
