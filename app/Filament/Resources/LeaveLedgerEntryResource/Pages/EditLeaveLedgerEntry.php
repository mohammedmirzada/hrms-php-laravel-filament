<?php

namespace App\Filament\Resources\LeaveLedgerEntryResource\Pages;

use App\Filament\Resources\LeaveLedgerEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveLedgerEntry extends EditRecord
{
    protected static string $resource = LeaveLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
