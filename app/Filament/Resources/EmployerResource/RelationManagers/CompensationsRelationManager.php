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
                            ->required()
                            ->helperText('The pay template to use. Only active structures are shown. The structure defines which allowances and deductions apply on top of the basic salary.'),
                        Select::make('currency_code')
                            ->native(false)
                            ->label('Currency')
                            ->options(config('currency'))
                            ->default('USD')
                            ->searchable()
                            ->required()
                            ->helperText('The currency this salary is paid in. Can differ from the payroll processing currency — the system will convert automatically.'),
                        TextInput::make('basic_salary')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->helperText('The employee\'s base monthly salary. All percentage-based items in the salary structure are calculated from this number (e.g. a 10% housing allowance = 10% of this amount).'),
                        DatePicker::make('effective_from')
                            ->native(false)
                            ->required()
                            ->helperText('The date this salary takes effect. Adding a new compensation automatically closes the previous one on this date.'),
                        DatePicker::make('effective_to')
                            ->native(false)
                            ->after('effective_from')
                            ->helperText('Leave empty — this is set automatically when a newer compensation is added. Only fill it manually if you are entering historical records.'),
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
