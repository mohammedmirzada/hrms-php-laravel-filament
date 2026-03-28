<?php

namespace App\Filament\Resources\PayrollPeriodResource\Pages;

use App\Filament\Resources\PayrollPeriodResource;
use Filament\Resources\Pages\EditRecord;

class EditPayrollPeriod extends EditRecord
{
    protected static string $resource = PayrollPeriodResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->immutable) {
            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
        }
    }
}
