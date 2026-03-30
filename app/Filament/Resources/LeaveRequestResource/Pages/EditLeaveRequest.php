<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

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
