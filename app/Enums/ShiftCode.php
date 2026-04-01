<?php

namespace App\Enums;

enum ShiftCode: string
{
    case Morning   = 'MORNING';
    case Afternoon = 'AFTERNOON';
    case Night     = 'NIGHT';

    public function label(): string
    {
        return match ($this) {
            self::Morning   => 'Morning',
            self::Afternoon => 'Afternoon',
            self::Night     => 'Night',
        };
    }

    public static function labels(): array
    {
        return array_column(
            array_map(fn ($c) => ['k' => $c->value, 'l' => $c->label()], self::cases()),
            'l', 'k'
        );
    }
}
