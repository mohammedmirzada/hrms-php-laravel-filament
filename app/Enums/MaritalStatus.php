<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case Single   = 'single';
    case Married  = 'married';
    case Divorced = 'divorced';
    case Widowed  = 'widowed';

    public function label(): string
    {
        return match ($this) {
            self::Single   => 'Single',
            self::Married  => 'Married',
            self::Divorced => 'Divorced',
            self::Widowed  => 'Widowed',
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
