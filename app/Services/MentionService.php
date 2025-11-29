<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\UserMentionedNotification;

class MentionService
{
    /**
     * @return array<int>
     */
    public function extractMentionedUserIds(string $content): array
    {
        // Match @Username patterns (username can have spaces until end or next @)
        preg_match_all('/@([^@\n]+?)(?=\s*@|\s*<|$)/u', strip_tags($content), $matches);

        if (empty($matches[1])) {
            return [];
        }

        $userNames = array_map('trim', $matches[1]);
        $userNames = array_filter($userNames);

        return User::whereIn('name', $userNames)
            ->pluck('id')
            ->toArray();
    }

    public function notifyMentionedUsers(string $content, Ticket $ticket, User $author, string $context = 'comment'): void
    {
        $mentionedUserIds = $this->extractMentionedUserIds($content);

        // Don't notify the author if they mentioned themselves
        $mentionedUserIds = array_filter($mentionedUserIds, fn ($id) => $id !== $author->id);

        if (empty($mentionedUserIds)) {
            return;
        }

        $users = User::whereIn('id', $mentionedUserIds)->get();

        foreach ($users as $user) {
            $user->notify(new UserMentionedNotification($ticket, $author, $context));
        }
    }
}
