<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

use App\Forms\Components\MentionRichEditor;
use App\Services\MentionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comentarios';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),

                MentionRichEditor::make('body')
                    ->label('Comentario')
                    ->required()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('ticket-comments')
                    ->fileAttachmentsVisibility('public')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn ($record) => 'Comentario de '.($record->user?->name ?? 'Usuario eliminado'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Autor')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('body')
                    ->label('Comentario')
                    ->html()
                    ->limit(100)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar comentario')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    })
                    ->after(function ($record) {
                        $mentionService = app(MentionService::class);
                        $mentionService->notifyMentionedUsers(
                            $record->body,
                            $record->ticket,
                            auth()->user(),
                            'comment'
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->user_id === auth()->id()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->user_id === auth()->id() || auth()->user()->hasRole('Admin')),
            ])
            ->bulkActions([]);
    }
}
