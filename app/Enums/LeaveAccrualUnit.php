<?php

namespace App\Enums;

enum LeaveAccrualUnit: string
{
    case DayPerMonth  = 'DAY_PER_MONTH';
    case HourPerMonth = 'HOUR_PER_MONTH';
    case DayPerYear   = 'DAY_PER_YEAR';
    case HourPerYear  = 'HOUR_PER_YEAR';

    public function label(): string
    {
        return match ($this) {
            self::DayPerMonth  => 'Day / Month',
            self::HourPerMonth => 'Hour / Month',
            self::DayPerYear   => 'Day / Year',
            self::HourPerYear  => 'Hour / Year',
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
