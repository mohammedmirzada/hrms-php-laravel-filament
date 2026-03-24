<?php

namespace App\Filament\Resources\EmployerShifts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployerShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('employer.full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->employer?->getTranslation('full_name', 'en'))
                    ->searchable()->sortable(),
                TextColumn::make('shift.code')
                    ->label('Shift')->badge()->sortable(),
                TextColumn::make('shift.start_time')
                    ->label('Start'),
                TextColumn::make('shift.end_time')
                    ->label('End'),
                TextColumn::make('effective_from')->date()->sortable(),
                TextColumn::make('effective_to')->date()->placeholder('Active')->sortable(),
            ])
            ->filters([
                SelectFilter::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'code')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
