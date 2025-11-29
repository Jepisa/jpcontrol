<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case OnHold = 'on_hold';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::InProgress => 'En Progreso',
            self::Resolved => 'Resuelto',
            self::Closed => 'Cerrado',
            self::OnHold => 'En Espera',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'info',
            self::InProgress => 'warning',
            self::Resolved => 'success',
            self::Closed => 'gray',
            self::OnHold => 'danger',
        };
    }
}
