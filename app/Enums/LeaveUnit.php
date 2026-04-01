<?php

namespace App\Enums;

enum LeaveUnit: string
{
    case Hour = 'HOUR';
    case Day  = 'DAY';

    public function label(): string
    {
        return match ($this) {
            self::Hour => 'Hour',
            self::Day  => 'Day',
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
