<?php

namespace App\Enums;

enum EmergencyContactRelation: string
{
    case Spouse  = 'spouse';
    case Parent  = 'parent';
    case Sibling = 'sibling';
    case Child   = 'child';
    case Friend  = 'friend';
    case Other   = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spouse  => 'Spouse',
            self::Parent  => 'Parent',
            self::Sibling => 'Sibling',
            self::Child   => 'Child',
            self::Friend  => 'Friend',
            self::Other   => 'Other',
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
