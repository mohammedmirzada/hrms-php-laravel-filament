<?php

namespace App\Filament\Resources\EmployerResource\RelationManagers;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompensationsRelationManager extends RelationManager
{
    protected static string $relationship = 'compensations';

    protected static ?string $title = 'Compensation History';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Banknotes;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('salary_structure_id')
                            ->relationship('salaryStructure', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('currency_code')
                            ->label('Currency')
                            ->default('USD')
                            ->maxLength(13)
                            ->required(),
                        TextInput::make('basic_salary')
                            ->numeric()
                            ->required(),
                        DatePicker::make('effective_from')
                            ->required(),
                        DatePicker::make('effective_to'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('salaryStructure.name')
                    ->label('Structure')
                    ->sortable(),
                TextColumn::make('currency_code')
                    ->label('Currency'),
                TextColumn::make('basic_salary')
                    ->money(fn ($record) => strtolower($record->currency_code))
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->date()
                    ->sortable()
                    ->placeholder('Current'),
            ])
            ->defaultSort('effective_from', 'desc')
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
