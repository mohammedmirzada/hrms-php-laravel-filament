<?php

namespace App\Filament\Resources\EmployerResource\RelationManagers;

use App\Models\EmployerCompensation;
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

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('salary_structure_id')
                            ->native(false)
                            ->relationship('salaryStructure', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('currency_code')
                            ->native(false)
                            ->label('Currency')
                            ->options(config('currency'))
                            ->default('USD')
                            ->searchable()
                            ->required(),
                        TextInput::make('basic_salary')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        DatePicker::make('effective_from')
                            ->native(false)
                            ->required(),
                        DatePicker::make('effective_to')
                            ->native(false)
                            ->after('effective_from'),
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
                    ->money(fn ($record) => $record->currency_code)
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->toFormattedDateString() : 'Current')
                    ->badge()
                    ->color(fn ($record) => $record->effective_to === null ? 'success' : 'gray')
                    ->sortable(),
            ])
            ->defaultSort('effective_from', 'desc')
            ->headerActions([
                Actions\CreateAction::make()
                    ->after(function ($record) {
                        EmployerCompensation::where('employer_id', $record->employer_id)
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
