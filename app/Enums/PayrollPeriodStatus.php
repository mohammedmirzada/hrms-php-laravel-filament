<?php

namespace App\Enums;

enum PayrollPeriodStatus: string
{
    case Open       = 'open';
    case Calculated = 'calculated';
    case Approved   = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Open       => 'Open',
            self::Calculated => 'Calculated',
            self::Approved   => 'Approved',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open       => 'info',
            self::Calculated => 'warning',
            self::Approved   => 'success',
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
