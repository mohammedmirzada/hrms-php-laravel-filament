<?php

namespace App\Enums;

enum ApprovalRole: string
{
    case Manager = 'MANAGER';
    case Hr      = 'HR';
    case Final   = 'FINAL';

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'Manager',
            self::Hr      => 'HR',
            self::Final   => 'Final',
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
