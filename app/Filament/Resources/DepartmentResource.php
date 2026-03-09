<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslatableFields;
use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DepartmentResource extends Resource
{
    use HasTranslatableFields;

    protected static ?string $model = Department::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::RectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                static::translatableTabs('name', 'Department Name', required: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('name')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employers_count')
                    ->label('Employees')
                    ->counts('employers')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
