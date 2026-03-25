<?php

namespace App\Filament\Resources\SalaryStructureResource\RelationManagers;

use App\Filament\Concerns\HasTranslatableFields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    use HasTranslatableFields;

    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                static::translatableTabs('name', 'Item Name', required: true),
                Select::make('type')
                    ->native(false)
                    ->options([
                        'earning' => 'Earning',
                        'deduction' => 'Deduction',
                    ])
                    ->required()
                    ->helperText('Earnings add to the employee\'s pay (e.g. housing allowance, bonus). Deductions reduce it (e.g. loan repayment, penalty).'),
                Select::make('calculation_type')
                    ->native(false)
                    ->options([
                        'fixed' => 'Fixed Amount',
                        'percentage' => 'Percentage',
                        'manual' => 'Manual',
                    ])
                    ->required()
                    ->live()
                    ->helperText('Fixed: same amount every month. Percentage: calculated from the base salary. Manual: entered separately each payroll period (e.g. overtime).'),
                TextInput::make('value')
                    ->numeric()
                    ->required()
                    ->helperText(fn ($get) => match ($get('calculation_type')) {
                        'fixed'      => 'Enter the flat amount paid every month in the structure\'s currency (e.g. 200 means $200/month).',
                        'percentage' => 'Enter the percentage of the base salary (e.g. 10 means 10% of basic salary).',
                        'manual'     => 'This is a default/reference value only. The actual amount will be entered manually each time payroll is run.',
                        default      => 'Enter the amount or percentage depending on the calculation type selected above.',
                    }),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'earning' => 'success',
                        'deduction' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('calculation_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('value')
                    ->numeric()
                    ->sortable(),
            ])
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
