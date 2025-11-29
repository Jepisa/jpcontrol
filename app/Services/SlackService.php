<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService
{
    public function __construct(
        protected string $webhookUrl,
        protected string $channel
    ) {}

    public function sendTicketNotification(Ticket $ticket): ?string
    {
        try {
            $message = $this->buildTicketMessage($ticket);

            $response = Http::post($this->webhookUrl, $message);

            if ($response->successful()) {
                Log::info('Ticket notification sent to Slack', ['ticket_id' => $ticket->id]);
                $data = $response->json();

                return $data['ts'] ?? null;
            }

            Log::error('Failed to send ticket to Slack', [
                'ticket_id' => $ticket->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception sending ticket to Slack', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function buildTicketMessage(Ticket $ticket): array
    {
        $priorityEmoji = match ($ticket->priority->value) {
            'critical' => '🔴',
            'high' => '🟠',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪',
        };

        // Convertir descripción a texto plano formateado
        $description = $this->convertHtmlToSlackFormat($ticket->description);

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => "{$priorityEmoji} Nuevo Ticket #{$ticket->id}",
                    'emoji' => true,
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Título:*\n{$ticket->title}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Prioridad:*\n{$ticket->priority->getLabel()}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Creado por:*\n{$ticket->user->name}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Estado:*\n{$ticket->status->getLabel()}",
                    ],
                ],
            ],
        ];

        if ($ticket->store_name) {
            $blocks[] = [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Tienda:*\n{$ticket->store_name}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Entorno:*\n".($ticket->environment?->getLabel() ?? 'N/A'),
                    ],
                ],
            ];
        }

        // Usar plain_text para la descripción para evitar problemas de escape
        $blocks[] = [
            'type' => 'section',
            'text' => [
                'type' => 'plain_text',
                'text' => "Descripción:\n{$description}",
                'emoji' => true,
            ],
        ];

        if ($ticket->steps_to_reproduce) {
            $stepsFormatted = $this->convertHtmlToSlackFormat($ticket->steps_to_reproduce);
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'plain_text',
                    'text' => "Pasos para reproducir:\n{$stepsFormatted}",
                    'emoji' => true,
                ],
            ];
        }

        $blocks[] = [
            'type' => 'divider',
        ];

        // Agregar botón para ir al ticket
        $ticketUrl = config('app.url').'/admin/tickets/'.$ticket->id.'/edit';
        $blocks[] = [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'Ver Ticket',
                        'emoji' => true,
                    ],
                    'url' => $ticketUrl,
                    'style' => 'primary',
                ],
            ],
        ];

        return [
            'text' => "Nuevo Ticket #{$ticket->id}: {$ticket->title}",
            'blocks' => $blocks,
        ];
    }

    protected function convertHtmlToSlackFormat(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Convertir saltos de línea y párrafos
        $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
        $html = str_replace('</p>', "\n", $html);
        $html = str_replace('</li>', "\n", $html);

        // Eliminar todas las etiquetas HTML
        $text = strip_tags($html);

        // Decodificar entidades HTML
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Limpiar espacios múltiples en la misma línea
        $text = preg_replace('/ +/', ' ', $text);

        // Limpiar múltiples saltos de línea (más de 2 seguidos)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Limpiar espacios al inicio y final de cada línea
        $lines = array_map('trim', explode("\n", $text));
        $text = implode("\n", array_filter($lines, fn ($line) => $line !== ''));

        return trim($text);
    }
}
