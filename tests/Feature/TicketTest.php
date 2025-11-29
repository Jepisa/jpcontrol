<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Resources\TicketResource\RelationManagers\CommentsRelationManager;
use App\Jobs\CreateJiraIssueJob;
use App\Jobs\SendTicketToSlackJob;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_tickets(): void
    {
        $tickets = Ticket::factory()->count(3)->create();

        Livewire::test(ListTickets::class)
            ->assertCanSeeTableRecords($tickets);
    }

    public function test_can_create_ticket(): void
    {
        Queue::fake();

        $ticketData = [
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket description',
            'priority' => TicketPriority::High->value,
            'status' => TicketStatus::Open->value,
            'store_name' => 'Test Store',
            'user_id' => $this->user->id,
        ];

        Livewire::test(CreateTicket::class)
            ->fillForm($ticketData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tickets', [
            'title' => 'Test Ticket',
            'store_name' => 'Test Store',
            'user_id' => $this->user->id,
        ]);

        Queue::assertPushed(SendTicketToSlackJob::class);
        Queue::assertPushed(CreateJiraIssueJob::class);
    }

    public function test_can_edit_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::test(EditTicket::class, ['record' => $ticket->id])
            ->fillForm([
                'title' => 'Updated Title',
                'status' => TicketStatus::InProgress->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Updated Title',
            'status' => TicketStatus::InProgress->value,
        ]);
    }

    public function test_can_filter_tickets_by_status(): void
    {
        $openTicket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $closedTicket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

        Livewire::test(ListTickets::class)
            ->filterTable('status', TicketStatus::Open->value)
            ->assertCanSeeTableRecords([$openTicket])
            ->assertCanNotSeeTableRecords([$closedTicket]);
    }

    public function test_can_filter_tickets_by_priority(): void
    {
        $highPriorityTicket = Ticket::factory()->create(['priority' => TicketPriority::High]);
        $lowPriorityTicket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

        Livewire::test(ListTickets::class)
            ->filterTable('priority', TicketPriority::High->value)
            ->assertCanSeeTableRecords([$highPriorityTicket])
            ->assertCanNotSeeTableRecords([$lowPriorityTicket]);
    }

    public function test_can_search_tickets(): void
    {
        $ticket1 = Ticket::factory()->create(['title' => 'Bug in checkout']);
        $ticket2 = Ticket::factory()->create(['title' => 'Feature request']);

        Livewire::test(ListTickets::class)
            ->searchTable('checkout')
            ->assertCanSeeTableRecords([$ticket1])
            ->assertCanNotSeeTableRecords([$ticket2]);
    }

    public function test_ticket_requires_title(): void
    {
        Livewire::test(CreateTicket::class)
            ->fillForm([
                'title' => '',
                'description' => 'Test description',
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_ticket_requires_description(): void
    {
        Livewire::test(CreateTicket::class)
            ->fillForm([
                'title' => 'Test Title',
                'description' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['description' => 'required']);
    }

    public function test_can_create_comment_on_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        Livewire::test(CommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => EditTicket::class,
        ])
            ->callTableAction('create', data: [
                'body' => '<p>This is a test comment</p>',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => '<p>This is a test comment</p>',
        ]);
    }

    public function test_can_edit_own_comment(): void
    {
        $ticket = Ticket::factory()->create();
        $comment = TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => '<p>Original comment</p>',
        ]);

        Livewire::test(CommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => EditTicket::class,
        ])
            ->callTableAction('edit', $comment, data: [
                'body' => '<p>Updated comment</p>',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('ticket_comments', [
            'id' => $comment->id,
            'body' => '<p>Updated comment</p>',
        ]);
    }

    public function test_cannot_edit_other_user_comment(): void
    {
        $ticket = Ticket::factory()->create();
        $otherUser = User::factory()->create();
        $comment = TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $otherUser->id,
            'body' => '<p>Other user comment</p>',
        ]);

        Livewire::test(CommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => EditTicket::class,
        ])
            ->assertTableActionHidden('edit', $comment);
    }

    public function test_can_delete_own_comment(): void
    {
        $ticket = Ticket::factory()->create();
        $comment = TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
        ]);

        Livewire::test(CommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => EditTicket::class,
        ])
            ->callTableAction('delete', $comment)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('ticket_comments', [
            'id' => $comment->id,
        ]);
    }
}
