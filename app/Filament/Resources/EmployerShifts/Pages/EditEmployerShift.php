<?php

namespace App\Filament\Resources\EmployerShifts\Pages;

use App\Filament\Resources\EmployerShifts\EmployerShiftResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployerShift extends EditRecord
{
    protected static string $resource = EmployerShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
