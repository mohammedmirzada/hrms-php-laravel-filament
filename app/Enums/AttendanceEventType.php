<?php

namespace App\Enums;

enum AttendanceEventType: string
{
    case In  = 'IN';
    case Out = 'OUT';

    public function label(): string
    {
        return match ($this) {
            self::In  => 'Check In',
            self::Out => 'Check Out',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::In  => 'success',
            self::Out => 'danger',
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
