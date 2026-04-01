<?php

namespace App\Enums;

enum LeaveRequestStatus: string
{
    case Draft           = 'DRAFT';
    case Submitted       = 'SUBMITTED';
    case ManagerApproved = 'MANAGER_APPROVED';
    case HrApproved      = 'HR_APPROVED';
    case FinalApproved   = 'FINAL_APPROVED';
    case Rejected        = 'REJECTED';
    case Cancelled       = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::Draft           => 'Draft',
            self::Submitted       => 'Submitted',
            self::ManagerApproved => 'Manager Approved',
            self::HrApproved      => 'HR Approved',
            self::FinalApproved   => 'Final Approved',
            self::Rejected        => 'Rejected',
            self::Cancelled       => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft           => 'gray',
            self::Submitted       => 'info',
            self::ManagerApproved => 'warning',
            self::HrApproved      => 'warning',
            self::FinalApproved   => 'success',
            self::Rejected        => 'danger',
            self::Cancelled       => 'gray',
        };
    }

    /** @return array<string, string> value => label */
    public static function labels(): array
    {
        return array_column(
            array_map(fn ($case) => ['key' => $case->value, 'label' => $case->label()], self::cases()),
            'label', 'key'
        );
    }

    /** @return array<string, string> value => color */
    public static function colors(): array
    {
        return array_column(
            array_map(fn ($case) => ['key' => $case->value, 'color' => $case->color()], self::cases()),
            'color', 'key'
        );
    }

    /** @return array<string, string[]> value => allowed next values */
    public static function transitions(): array
    {
        return [
            self::Draft->value           => [self::Submitted->value, self::Cancelled->value],
            self::Submitted->value       => [self::ManagerApproved->value, self::HrApproved->value, self::FinalApproved->value, self::Rejected->value, self::Cancelled->value],
            self::ManagerApproved->value => [self::HrApproved->value, self::FinalApproved->value, self::Rejected->value, self::Cancelled->value],
            self::HrApproved->value      => [self::FinalApproved->value, self::Rejected->value, self::Cancelled->value],
            self::FinalApproved->value   => [],
            self::Rejected->value        => [],
            self::Cancelled->value       => [],
        ];
    }
}
