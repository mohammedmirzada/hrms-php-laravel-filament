<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslatableFields;
use App\Filament\Resources\EmploymentStatusResource\Pages;
use App\Models\EmploymentStatus;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class EmploymentStatusResource extends Resource
{
    use HasTranslatableFields;

    protected static ?string $model = EmploymentStatus::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                static::translatableTabs('name', 'Status Name', required: true),
                TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->helperText('A short unique key used in reports and payroll badges (e.g. active, suspended, resigned). Once saved, avoid changing it as other records may depend on it.'),
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
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('employers_count')
                    ->label('Employees')
                    ->counts('employers')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_by')
                    ->label('Created By')
                    ->formatStateUsing(fn ($record) => $record->createdBy?->name)
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
            'index' => Pages\ListEmploymentStatuses::route('/'),
            'create' => Pages\CreateEmploymentStatus::route('/create'),
            'edit' => Pages\EditEmploymentStatus::route('/{record}/edit'),
        ];
    }
}
