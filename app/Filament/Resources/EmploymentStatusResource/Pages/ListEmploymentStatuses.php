<?php

namespace App\Filament\Resources\EmploymentStatusResource\Pages;

use App\Filament\Resources\EmploymentStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmploymentStatuses extends ListRecords
{
    protected static string $resource = EmploymentStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
