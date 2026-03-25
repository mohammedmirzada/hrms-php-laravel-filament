<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeRateResource\Pages;
use App\Models\ExchangeRate;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ExchangeRateResource extends Resource
{
    protected static ?string $model = ExchangeRate::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::CurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Payroll & Compensation';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('base_code')
                    ->native(false)
                    ->label('Base Currency')
                    ->options(config('currency'))
                    ->required()
                    ->searchable()
                    ->helperText('The currency your salaries are originally defined in (e.g. USD).'),
                Select::make('quote_currency')
                    ->native(false)
                    ->label('Quote Currency')
                    ->options(config('currency'))
                    ->required()
                    ->searchable()
                    ->helperText('The currency you want to convert to — usually the local currency where employees are paid (e.g. ETB, IQD).'),
                TextInput::make('rate')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->helperText('How much 1 unit of the base currency equals in the quote currency. Example: if 1 USD = 57.5 ETB, enter 57.5.'),
                DatePicker::make('rate_date')
                    ->native(false)
                    ->label('Rate Date')
                    ->required()
                    ->default(now())
                    ->helperText('The date this exchange rate was recorded. Payroll periods will use the rate matching their "Exchange Rate Date".'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('base_code')
                    ->label('Base')
                    ->badge()
                    ->sortable(),
                TextColumn::make('quote_currency')
                    ->label('Quote')
                    ->badge()
                    ->sortable(),
                TextColumn::make('rate')
                    ->numeric(6)
                    ->sortable(),
                TextColumn::make('rate_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('base_code')
                    ->label('Base Currency')
                    ->options(config('currency'))
                    ->searchable()
                    ->native(false),
                SelectFilter::make('quote_currency')
                    ->label('Quote Currency')
                    ->options(config('currency'))
                    ->searchable()
                    ->native(false)
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
            ->defaultSort('rate_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExchangeRates::route('/'),
            'create' => Pages\CreateExchangeRate::route('/create'),
            'edit' => Pages\EditExchangeRate::route('/{record}/edit'),
        ];
    }
}
