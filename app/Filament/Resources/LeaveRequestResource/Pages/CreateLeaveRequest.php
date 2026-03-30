<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Filament\Resources\LeaveRequestResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return static::getModel()::create($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Notification::make()
                ->title('Validation error')
                ->body(collect($e->errors())->flatten()->first())
                ->danger()
                ->send();

            $this->halt();

            return app(static::getModel());
        }
    }
}
