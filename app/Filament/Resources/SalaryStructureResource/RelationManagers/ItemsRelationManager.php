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
                    ->options([
                        'earning' => 'Earning',
                        'deduction' => 'Deduction',
                    ])
                    ->required(),
                Select::make('calculation_type')
                    ->options([
                        'fixed' => 'Fixed Amount',
                        'percentage' => 'Percentage',
                        'manual' => 'Manual',
                    ])
                    ->required(),
                TextInput::make('value')
                    ->numeric()
                    ->required(),
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
