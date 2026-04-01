<?php

namespace App\Enums;

enum LeaveRequestDayPart: string
{
    case FullDay    = 'FULL_DAY';
    case HalfDayAm  = 'HALF_DAY_AM';
    case HalfDayPm  = 'HALF_DAY_PM';
    case Hourly     = 'HOURLY';

    public function label(): string
    {
        return match ($this) {
            self::FullDay   => 'Full Day',
            self::HalfDayAm => 'Half Day AM',
            self::HalfDayPm => 'Half Day PM',
            self::Hourly    => 'Hourly',
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
