<?php

namespace App\Enums;

enum LeaveDocumentType: string
{
    case MedicalCertificate  = 'Medical Certificate';
    case DeathCertificate    = 'Death Certificate';
    case MarriageCertificate = 'Marriage Certificate';
    case CourtOrder          = 'Court Order';
    case TravelDocument      = 'Travel Document';
    case Other               = 'Other';

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
