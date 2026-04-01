<?php

namespace App\Enums;

enum AttendanceDeviceSyncMode: string
{
    case Push   = 'push';
    case Pull   = 'pull';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Push   => 'Push',
            self::Pull   => 'Pull',
            self::Manual => 'Manual',
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
