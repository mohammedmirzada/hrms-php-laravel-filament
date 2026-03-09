<?php

namespace App\Filament\Resources\AttendanceBranchSettingResource\Pages;

use App\Filament\Resources\AttendanceBranchSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceBranchSetting extends EditRecord
{
    protected static string $resource = AttendanceBranchSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
