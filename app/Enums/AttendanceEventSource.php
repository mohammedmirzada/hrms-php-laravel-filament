<?php

namespace App\Enums;

enum AttendanceEventSource: string
{
    case Biometric = 'BIOMETRIC';
    case Mobile    = 'MOBILE';

    public function label(): string
    {
        return match ($this) {
            self::Biometric => 'Biometric',
            self::Mobile    => 'Mobile',
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
