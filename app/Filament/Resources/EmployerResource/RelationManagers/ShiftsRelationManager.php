<?php

namespace App\Filament\Resources\EmployerResource\RelationManagers;

use App\Models\EmployerShift;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShiftsRelationManager extends RelationManager
{
    protected static string $relationship = 'employerShifts';

    protected static ?string $title = 'Shift History';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Clock;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('shift_id')
                    ->native(false)
                    ->label('Shift')
                    ->relationship('shift', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('name', 'en'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Select the shift assigned to this employee.'),
                DatePicker::make('effective_from')
                    ->native(false)
                    ->label('From')
                    ->required()
                    ->helperText('The date this shift assignment starts.'),
                DatePicker::make('effective_to')
                    ->native(false)
                    ->label('To')
                    ->nullable()
                    ->after('effective_from')
                    ->helperText('Leave empty if this is the current active shift.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shift.name')
                    ->label('Shift')
                    ->formatStateUsing(fn ($record) => $record->shift?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('shift.start_time')
                    ->label('Start')
                    ->sortable(),
                TextColumn::make('shift.end_time')
                    ->label('End')
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->label('From')
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->label('To')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->toFormattedDateString() : 'Current')
                    ->badge()
                    ->color(fn ($record) => $record->effective_to === null ? 'success' : 'gray')
                    ->sortable(),
            ])
            ->defaultSort('effective_from', 'desc')
            ->headerActions([
                Actions\CreateAction::make()
                    ->after(function ($record) {
                        EmployerShift::where('employer_id', $record->employer_id)
                            ->where('id', '!=', $record->id)
                            ->whereNull('effective_to')
                            ->update(['effective_to' => today()]);
                    }),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
