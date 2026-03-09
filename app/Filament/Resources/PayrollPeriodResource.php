<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayrollPeriodResource\Pages;
use App\Models\Branch;
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
                            ->preload(),
                        DatePicker::make('period_start')
                            ->native(false)
                            ->required(),
                        DatePicker::make('period_end')
                            ->native(false)
                            ->required()
                            ->after('period_start'),
                        Select::make('processing_currency_code')
                            ->native(false)
                            ->label('Processing Currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'IQD' => 'IQD',
                                'TRY' => 'TRY',
                            ])
                            ->required()
                            ->searchable(),
                        DatePicker::make('exchange_rate_date')
                            ->native(false)
                            ->label('Exchange Rate Date')
                            ->nullable(),
                        Select::make('status')
                            ->native(false)
                            ->options([
                                'open' => 'Open',
                                'attendance_locked' => 'Attendance Locked',
                                'calculated' => 'Calculated',
                                'approved' => 'Approved',
                            ])
                            ->default('open')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Locks & Approvals')
                    ->schema([
                        Toggle::make('immutable')
                            ->label('Immutable (Finalized)')
                            ->helperText('Once enabled, no further changes can be made'),
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
                        'attendance_locked' => 'warning',
                        'calculated' => 'primary',
                        'approved' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('immutable')
                    ->label('Finalized')
                    ->boolean(),
                TextColumn::make('attendanceLockedByUser.name')
                    ->label('Locked By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('attendance_locked_at')
                    ->label('Locked At')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->relationship('branch', 'name'),
                SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'attendance_locked' => 'Attendance Locked',
                        'calculated' => 'Calculated',
                        'approved' => 'Approved',
                    ]),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
