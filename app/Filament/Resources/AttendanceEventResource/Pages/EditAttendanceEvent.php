<?php

namespace App\Filament\Resources\AttendanceEventResource\Pages;

use App\Filament\Resources\AttendanceEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceEvent extends EditRecord
{
    protected static string $resource = AttendanceEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
