<?php

namespace App\Filament\Resources\EmployerResource\Pages;

use App\Filament\Resources\EmployerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployer extends ViewRecord
{
    protected static string $resource = EmployerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
