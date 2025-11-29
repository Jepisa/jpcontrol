<?php

namespace App\Filament\Resources;

use App\Enums\TicketEnvironment;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Forms\Components\MentionRichEditor;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?string $modelLabel = 'Ticket';

    protected static ?string $pluralModelLabel = 'Tickets';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Ticket')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        MentionRichEditor::make('description')
                            ->label('Descripción')
                            ->required()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('ticket-descriptions')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('priority')
                            ->label('Prioridad')
                            ->options(TicketPriority::class)
                            ->required()
                            ->default(TicketPriority::Medium),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(TicketStatus::class)
                            ->required()
                            ->default(TicketStatus::Open)
                            ->disabled(fn ($context) => $context === 'create'),

                        Forms\Components\TextInput::make('store_name')
                            ->label('Tienda')
                            ->helperText('Nombre de la tienda específica relacionada con este ticket')
                            ->maxLength(255),

                        Forms\Components\Select::make('environment')
                            ->label('Entorno')
                            ->options(TicketEnvironment::class)
                            ->required()
                            ->default(TicketEnvironment::Production),

                        Forms\Components\Select::make('user_id')
                            ->label('Reportado por')
                            ->relationship('user', 'name')
                            ->required()
                            ->default(fn () => auth()->id())
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Select::make('assigned_to')
                            ->label('Asignado a')
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin asignar'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Información de Integraciones')
                    ->schema([
                        Forms\Components\TextInput::make('slack_message_ts')
                            ->label('Slack Message ID')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('jira_issue_key')
                            ->label('Jira Issue Key')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->hidden(fn ($context) => $context === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('store_name')
                    ->label('Tienda')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Reportado por')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('Asignado a')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Sin asignar')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jira_issue_key')
                    ->label('Jira')
                    ->sortable()
                    ->toggleable()
                    ->url(fn ($record) => $record->jira_issue_key
                        ? config('services.jira.url').'/browse/'.$record->jira_issue_key
                        : null)
                    ->openUrlInNewTab(),

                Tables\Columns\IconColumn::make('slack_message_ts')
                    ->label('Slack')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(TicketStatus::class)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options(TicketPriority::class)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('user')
                    ->label('Reportado por')
                    ->relationship('user', 'name')
                    ->multiple(),

                Tables\Filters\SelectFilter::make('assignee')
                    ->label('Asignado a')
                    ->relationship('assignee', 'name')
                    ->multiple(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
