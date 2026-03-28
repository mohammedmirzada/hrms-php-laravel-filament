<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollPeriodResource\Pages;
use App\Models\Branch;
use App\Models\ExchangeRate;
use App\Models\PayrollPeriod;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use UnitEnum;

class PayrollPeriodResource extends Resource
{
    protected static ?string $model = PayrollPeriod::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Calculator;

    protected static string|UnitEnum|null $navigationGroup = 'Payroll & Compensation';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Period Details')
                    ->schema([
                        Select::make('branch_id')
                            ->native(false)
                            ->label('Branch')
                            ->relationship('branch', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('The company branch this payroll period belongs to. Each branch runs its own payroll independently.'),
                        DatePicker::make('period_start')
                            ->native(false)
                            ->required()
                            ->helperText('The first day of the pay cycle (e.g. March 1).'),
                        DatePicker::make('period_end')
                            ->native(false)
                            ->required()
                            ->after('period_start')
                            ->helperText('The last day of the pay cycle (e.g. March 31). Must be after the start date.'),
                        Select::make('processing_currency_code')
                            ->native(false)
                            ->label('Processing Currency')
                            ->options(config('currency'))
                            ->default('USD')
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state && $state !== 'USD') {
                                    $latest = ExchangeRate::where('base_code', 'USD')
                                        ->where('quote_currency', $state)
                                        ->latest('rate_date')
                                        ->value('rate_date');
                                    $set('exchange_rate_date', $latest);
                                } else {
                                    $set('exchange_rate_date', null);
                                }
                            })

                            ->rules([
                                function () {
                                    return function (string $_attribute, $value, \Closure $fail) {
                                        if ($value && $value !== 'USD') {
                                            $exists = ExchangeRate::where('base_code', 'USD')
                                                ->where('quote_currency', $value)
                                                ->exists();
                                            if (! $exists) {
                                                $fail('No exchange rate found for ' . $value . '. Add one in Exchange Rates first.');
                                            }
                                        }
                                    };
                                },
                            ])
                            ->helperText('All salary amounts will be converted and finalized in this currency for this period. If not USD, the latest exchange rate will be applied automatically.'),
                        DatePicker::make('exchange_rate_date')
                            ->native(false)
                            ->label('Exchange Rate Date')
                            ->disabled()
                            ->dehydrated()
                            ->hidden(fn ($get) => !$get('processing_currency_code') || $get('processing_currency_code') === 'USD')
                            ->helperText('Automatically set to the most recent exchange rate available for the selected currency. Read-only.'),
                        Select::make('status')
                            ->native(false)
                            ->options([
                                'open' => 'Open',
                                'calculated' => 'Calculated',
                                'approved' => 'Approved',
                            ])
                            ->default('open')
                            ->required()
                            ->disabled()
                            ->dehydrated(fn ($record) => $record === null)
                            ->helperText('Always starts as Open. Advances automatically through workflow actions — cannot be changed manually.'),
                    ])
                    ->columns(2),

                Section::make('Locks & Approvals')
                    ->schema([
                        Toggle::make('immutable')
                            ->label('Immutable (Finalized)')
                            ->disabled(fn ($record) => $record?->immutable)
                            ->helperText('Once turned on, this payroll period is permanently locked and cannot be unlocked by anyone. Only enable this after the period is fully approved and paid.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('period_start')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end')
                    ->date()
                    ->sortable(),
                TextColumn::make('processing_currency_code')
                    ->label('Currency')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'info',
                        'calculated' => 'primary',
                        'approved' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('immutable')
                    ->label('Finalized')
                    ->boolean(),
                TextColumn::make('approvedByUser.name')
                    ->label('Approved By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'calculated' => 'Calculated',
                        'approved' => 'Approved',
                    ])
                    ->native(false)
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make()
                    ->hidden(fn ($record) => $record->immutable),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->before(function ($records, $action) {
                            if ($records->contains('immutable', true)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Cannot delete finalized periods')
                                    ->body('Deselect all finalized periods and try again.')
                                    ->send();
                                $action->halt();
                            }
                        }),
                ]),
            ])
            ->defaultSort('period_start', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollPeriods::route('/'),
            'create' => Pages\CreatePayrollPeriod::route('/create'),
            'view' => Pages\ViewPayrollPeriod::route('/{record}'),
            'edit' => Pages\EditPayrollPeriod::route('/{record}/edit'),
        ];
    }
}
