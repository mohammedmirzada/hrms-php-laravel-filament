<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Pending  = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Skipped  = 'SKIPPED';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Skipped  => 'Skipped',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending  => 'gray',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Skipped  => 'warning',
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
