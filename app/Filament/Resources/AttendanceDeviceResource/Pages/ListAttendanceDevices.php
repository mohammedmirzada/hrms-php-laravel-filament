<?php

namespace App\Filament\Resources\AttendanceDeviceResource\Pages;

use App\Filament\Resources\AttendanceDeviceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceDevices extends ListRecords
{
    protected static string $resource = AttendanceDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
