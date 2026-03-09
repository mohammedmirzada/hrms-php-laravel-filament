<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslatableFields;
use App\Filament\Resources\HolidayResource\Pages;
use App\Models\Branch;
use App\Models\Holiday;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class HolidayResource extends Resource
{
    use HasTranslatableFields;

    protected static ?string $model = Holiday::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::CalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                    ->required()
                    ->searchable()
                    ->preload(),
                static::translatableTabs('name', 'Holiday Name', required: true),
                DatePicker::make('date')
                    ->required(),
                Toggle::make('is_working_day_override')
                    ->label('Working Day Override')
                    ->helperText('Enable if this overrides a normal day off as a working day'),
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
                TextColumn::make('branch.name')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                IconColumn::make('is_working_day_override')
                    ->label('Working Override')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'edit' => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
