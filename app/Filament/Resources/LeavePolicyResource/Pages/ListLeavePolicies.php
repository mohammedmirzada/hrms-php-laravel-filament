<?php

namespace App\Filament\Resources\LeavePolicyResource\Pages;

use App\Filament\Resources\LeavePolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeavePolicies extends ListRecords
{
    protected static string $resource = LeavePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
