<?php

namespace App\Filament\Resources\EmployerShifts\Pages;

use App\Filament\Resources\EmployerShifts\EmployerShiftResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployerShifts extends ListRecords
{
    protected static string $resource = EmployerShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
