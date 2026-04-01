<?php

namespace App\Enums;

enum LeaveAccrualStartRule: string
{
    case HireDate      = 'HIRE_DATE';
    case AfterProbation = 'AFTER_PROBATION';
    case FixedDate     = 'FIXED_DATE';

    public function label(): string
    {
        return match ($this) {
            self::HireDate       => 'Hire Date',
            self::AfterProbation => 'After Probation',
            self::FixedDate      => 'Fixed Date',
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
