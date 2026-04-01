<?php

namespace App\Enums;

enum SalaryItemType: string
{
    case Earning   = 'earning';
    case Deduction = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::Earning   => 'Earning',
            self::Deduction => 'Deduction',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Earning   => 'success',
            self::Deduction => 'danger',
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
