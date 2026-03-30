<?php

namespace App\Filament\Resources\PayrollPeriodResource\Pages;

use App\Filament\Resources\PayrollPeriodResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            $record->fill($data)->save();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Notification::make()
                ->title('Validation error')
                ->body(collect($e->errors())->flatten()->first())
                ->danger()
                ->send();

            $this->halt();
        }

        return $record;
    }
}
