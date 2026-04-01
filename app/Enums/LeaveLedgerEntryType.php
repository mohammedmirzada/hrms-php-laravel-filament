<?php

namespace App\Enums;

enum LeaveLedgerEntryType: string
{
    case Accrual    = 'ACCRUAL';
    case Deduction  = 'DEDUCTION';
    case Adjustment = 'ADJUSTMENT';
    case Reversal   = 'REVERSAL';
    case Expiry     = 'EXPIRY';

    public function label(): string
    {
        return match ($this) {
            self::Accrual    => 'Accrual',
            self::Deduction  => 'Deduction',
            self::Adjustment => 'Adjustment',
            self::Reversal   => 'Reversal',
            self::Expiry     => 'Expiry',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Accrual    => 'success',
            self::Deduction  => 'danger',
            self::Adjustment => 'info',
            self::Reversal   => 'warning',
            self::Expiry     => 'gray',
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
