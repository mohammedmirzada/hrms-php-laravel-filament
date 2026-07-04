<?php

namespace App\Filament\Resources\EmployerResource\RelationManagers;

use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeavesRelationManager extends RelationManager
{
    protected static string $relationship = 'leaves';

    protected static ?string $title = 'Leaves';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::CalendarDateRange;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                DatePicker::make('date')
                    ->native(false)
                    ->required()
                    ->default(now())
                    ->helperText('The day the employee was on leave.'),
                TextInput::make('hours')
                    ->numeric()
                    ->minValue(0.5)
                    ->step(0.5)
                    ->required()
                    ->suffix('hours')
                    ->helperText('How many hours of leave on this day (e.g. 8 for a full day, 4 for half a day).'),
                TextInput::make('note')
                    ->maxLength(255)
                    ->helperText('Optional reason or note.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('hours')
                    ->suffix(' h')
                    ->sortable(),
                TextColumn::make('note')
                    ->limit(60)
                    ->placeholder('—'),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([
                Actions\CreateAction::make(),
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
