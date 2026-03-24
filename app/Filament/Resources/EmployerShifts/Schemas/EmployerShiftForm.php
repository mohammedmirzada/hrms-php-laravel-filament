<?php

namespace App\Filament\Resources\EmployerShifts\Schemas;

use App\Models\Employer;
use App\Models\Shift;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class EmployerShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employer_id')
                    ->native(false)
                    ->label('Employee')
                    ->relationship('employer', 'full_name')
                    ->getOptionLabelFromRecordUsing(fn (Employer $record) => $record->getTranslation('full_name', 'en'))
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('shift_id')
                    ->native(false)
                    ->label('Shift')
                    ->options(fn () => Shift::all()->mapWithKeys(fn (Shift $s) => [
                        $s->id => $s->getTranslation('name', 'en') . ' (' . $s->code . ' · ' . $s->start_time . ' – ' . $s->end_time . ')',
                    ]))
                    ->required()
                    ->searchable(),
                DatePicker::make('effective_from')
                    ->native(false)
                    ->required(),
                DatePicker::make('effective_to')
                    ->native(false)
                    ->helperText('Leave empty if this is the current active shift.')
                    ->nullable(),
            ]);
    }
}
