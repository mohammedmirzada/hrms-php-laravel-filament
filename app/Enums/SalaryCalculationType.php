<?php

namespace App\Enums;

enum SalaryCalculationType: string
{
    case Fixed      = 'fixed';
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::Fixed      => 'Fixed',
            self::Percentage => 'Percentage',
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
