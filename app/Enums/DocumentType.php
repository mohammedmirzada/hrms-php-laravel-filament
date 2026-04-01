<?php

namespace App\Enums;

enum DocumentType: string
{
    case IdCard         = 'ID Card';
    case Passport       = 'Passport';
    case DriverLicense  = 'Driver License';
    case WorkPermit     = 'Work Permit';
    case Visa           = 'Visa';
    case Contract       = 'Contract';
    case Certificate    = 'Certificate';
    case Degree         = 'Degree';
    case Cv             = 'CV';
    case Other          = 'Other';

    public function label(): string
    {
        return $this->value;
    }

    public static function labels(): array
    {
        return array_column(
            array_map(fn ($c) => ['k' => $c->value, 'l' => $c->label()], self::cases()),
            'l', 'k'
        );
    }
}
