<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Services\MentionService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Only notify if description was changed
        if ($this->record->wasChanged('description')) {
            $mentionService = app(MentionService::class);
            $mentionService->notifyMentionedUsers(
                $this->record->description,
                $this->record,
                auth()->user(),
                'description'
            );
        }
    }
}
