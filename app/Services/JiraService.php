<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JiraService
{
    public function __construct(
        protected string $jiraUrl,
        protected string $email,
        protected string $apiToken,
        protected string $projectKey
    ) {}

    public function createIssue(Ticket $ticket): ?string
    {
        try {
            $issueData = $this->buildIssueData($ticket);

            $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->jiraUrl}/rest/api/3/issue", $issueData);

            if ($response->successful()) {
                $data = $response->json();
                $issueKey = $data['key'] ?? null;

                Log::info('Jira issue created', [
                    'ticket_id' => $ticket->id,
                    'issue_key' => $issueKey,
                ]);

                return $issueKey;
            }

            Log::error('Failed to create Jira issue', [
                'ticket_id' => $ticket->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception creating Jira issue', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function buildIssueData(Ticket $ticket): array
    {
        $description = $this->buildDescription($ticket);

        $priority = $this->mapPriorityToJira($ticket->priority->value);

        $data = [
            'fields' => [
                'project' => [
                    'key' => $this->projectKey,
                ],
                'summary' => $ticket->title,
                'description' => [
                    'type' => 'doc',
                    'version' => 1,
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $description,
                                ],
                            ],
                        ],
                    ],
                ],
                'issuetype' => [
                    'name' => 'Task',
                ],
            ],
        ];

        // Agregar prioridad si está disponible
        if ($priority) {
            $data['fields']['priority'] = ['name' => $priority];
        }

        return $data;
    }

    protected function buildDescription(Ticket $ticket): string
    {
        $description = "Creado desde JPCONTROL\n\n";
        $description .= "Descripción:\n{$ticket->description}\n\n";

        if ($ticket->store_name) {
            $description .= "Tienda: {$ticket->store_name}\n";
        }

        if ($ticket->environment) {
            $description .= "Entorno: {$ticket->environment}\n";
        }

        if ($ticket->steps_to_reproduce) {
            $description .= "\nPasos para reproducir:\n{$ticket->steps_to_reproduce}\n";
        }

        if ($ticket->expected_behavior) {
            $description .= "\nComportamiento esperado:\n{$ticket->expected_behavior}\n";
        }

        if ($ticket->actual_behavior) {
            $description .= "\nComportamiento actual:\n{$ticket->actual_behavior}\n";
        }

        $description .= "\nCreado por: {$ticket->user->name} ({$ticket->user->email})";

        return $description;
    }

    protected function mapPriorityToJira(string $priority): ?string
    {
        return match ($priority) {
            'critical' => 'Highest',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            default => null,
        };
    }
}
