<?php

namespace App\Filament\Resources\Shifts\Schemas;

use App\Filament\Concerns\HasTranslatableFields;
use App\Models\Branch;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class ShiftForm
{
    use HasTranslatableFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->native(false)
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('code')
                    ->native(false)
                    ->label('Shift')
                    ->options([
                        'MORNING' => 'Morning',
                        'AFTERNOON' => 'Afternoon',
                        'NIGHT' => 'Night',
                    ])
                    ->required(),
                static::translatableTabs('name', 'Shift Name', required: true),
                TimePicker::make('start_time')
                    ->label('Start Time')
                    ->seconds(false)
                    ->required(),
                TimePicker::make('end_time')
                    ->label('End Time')
                    ->seconds(false)
                    ->required(),
                CheckboxList::make('days_of_week')
                    ->label('Working Days')
                    ->options([
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        7 => 'Sunday',
                    ])
                    ->columns(4)
                    ->required(),
            ]);
    }
}
