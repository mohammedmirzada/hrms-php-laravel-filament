<?php

namespace App\Enums;

enum SocialSecurityBaseRule: string
{
    case BasicOnly         = 'basic_only';
    case BasicPlusMarked   = 'basic_plus_marked';
    case Gross             = 'gross';

    public function label(): string
    {
        return match ($this) {
            self::BasicOnly       => 'Basic Only',
            self::BasicPlusMarked => 'Basic + Marked',
            self::Gross           => 'Gross',
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
