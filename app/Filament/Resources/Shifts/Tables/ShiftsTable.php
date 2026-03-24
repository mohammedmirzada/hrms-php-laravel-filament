<?php

namespace App\Filament\Resources\Shifts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('code')->badge()->sortable(),
                TextColumn::make('name')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('name', 'en')),
                TextColumn::make('start_time')->label('Start'),
                TextColumn::make('end_time')->label('End'),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('code')
                    ->label('Shift')
                    ->options([
                        'MORNING' => 'Morning',
                        'AFTERNOON' => 'Afternoon',
                        'NIGHT' => 'Night',
                    ])
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteBulkAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
