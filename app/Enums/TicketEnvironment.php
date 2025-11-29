<?php

namespace App\Enums;

enum TicketEnvironment: string
{
    case Production = 'production';
    case Qa = 'qa';

    public function getLabel(): string
    {
        return match ($this) {
            self::Production => 'Producción',
            self::Qa => 'QA',
        };
    }
}
