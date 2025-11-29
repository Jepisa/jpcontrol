<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserMentionedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public User $mentionedBy,
        public string $context = 'comment'
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $contextText = $this->context === 'comment' ? 'un comentario' : 'la descripción';

        return [
            'title' => 'Te mencionaron en un ticket',
            'body' => "{$this->mentionedBy->name} te mencionó en {$contextText} del ticket #{$this->ticket->id}",
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'mentioned_by' => $this->mentionedBy->name,
            'context' => $this->context,
        ];
    }
}
