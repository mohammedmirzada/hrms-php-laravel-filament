<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeavePolicyResource\Pages;
use App\Models\Branch;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

class LeavePolicyResource extends Resource
{
    protected static ?string $model = LeavePolicy::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::DocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Leave Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Policy Assignment')
                    ->compact()
                    ->schema([
                        Select::make('branch_id')
                            ->native(false)
                            ->label('Branch')
                            ->relationship('branch', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('leave_type_id')
                            ->native(false)
                            ->label('Leave Type')
                            ->relationship('leaveType', 'name')
                            ->getOptionLabelFromRecordUsing(fn (LeaveType $record) => $record->getTranslation('name', 'en'))
                            ->required()
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Accrual Settings')
                    ->compact()
                    ->description('If accrual is enabled, leave will be automatically added to employee balances based on the defined rate and unit. Accrual can start from hire date, after probation, or a fixed date each year.')
                    ->schema([
                        Toggle::make('accrual_enabled')
                            ->label('Enable Accrual')
                            ->reactive()
                            ->columnSpanFull(),
                        TextInput::make('accrual_rate')
                            ->numeric()
                            ->nullable(),
                        Select::make('accrual_unit')
                            ->native(false)
                            ->options([
                                'DAY_PER_MONTH' => 'Days per Month',
                                'HOUR_PER_MONTH' => 'Hours per Month',
                                'DAY_PER_YEAR' => 'Days per Year',
                                'HOUR_PER_YEAR' => 'Hours per Year',
                            ])
                            ->nullable(),
                        Select::make('accrual_start_rule')
                            ->native(false)
                            ->options([
                                'HIRE_DATE' => 'From Hire Date',
                                'AFTER_PROBATION' => 'After Probation',
                                'FIXED_DATE' => 'Fixed Date',
                            ])
                            ->nullable(),
                        TextInput::make('accrual_start_month_day')
                            ->label('Fixed Start (MM-DD)')
                            ->placeholder('01-01')
                            ->maxLength(5)
                            ->nullable(),
                        TextInput::make('annual_cap')
                            ->label('Annual Cap')
                            ->numeric()
                            ->nullable()
                            ->helperText('Maximum leave that can be accrued in a year. Leave will stop accruing once this cap is reached.')
                    ])
                    ->columns(3),

                Section::make('Carryover Settings')
                    ->compact()
                    ->description('If carryover is enabled, unused leave at the end of the period (usually year-end) can be carried over to the next period. You can set a cap on how many days can be carried over and an optional expiry date for the carried days.')
                    ->schema([
                        Toggle::make('carryover_enabled')
                            ->label('Enable Carryover')
                            ->reactive(),
                        TextInput::make('carryover_cap')
                            ->label('Carryover Cap')
                            ->numeric()
                            ->nullable(),
                        DatePicker::make('carryover_expiry_date')
                            ->native(false)
                            ->label('Carryover Expiry')
                            ->nullable(),
                    ])
                    ->columns(1),

                Section::make('Request Rules')
                    ->compact()
                    ->schema([
                        Toggle::make('allow_hourly')
                            ->label('Allow Hourly Requests'),
                        Toggle::make('allow_half_day')
                            ->label('Allow Half-Day'),
                        TextInput::make('min_request_unit_minutes')
                            ->label('Minimum Request (minutes)')
                            ->numeric()
                            ->nullable(),
                        Toggle::make('negative_balance_allowed')
                            ->label('Allow Negative Balance')
                            ->reactive(),
                        TextInput::make('negative_balance_limit')
                            ->label('Negative Balance Limit')
                            ->numeric()
                            ->nullable(),
                    ])
                    ->columns(3),

                Section::make('Approval Workflow')
                    ->compact()
                    ->schema([
                        Toggle::make('requires_manager_approval')
                            ->label('Manager Approval'),
                        Toggle::make('requires_hr_approval')
                            ->label('HR Approval'),
                        Toggle::make('requires_final_approval')
                            ->label('Final Approval'),
                    ])
                    ->columns(3),
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
                TextColumn::make('leaveType.name')
                    ->formatStateUsing(fn ($record) => $record->leaveType?->getTranslation('name', 'en'))
                    ->sortable(),
                IconColumn::make('accrual_enabled')
                    ->label('Accrual')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('annual_cap')
                    ->sortable()
                    ->placeholder('—'),
                IconColumn::make('carryover_enabled')
                    ->label('Carryover')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('requires_manager_approval')
                    ->label('Manager')
                    ->boolean(),
                IconColumn::make('requires_hr_approval')
                    ->label('HR')
                    ->boolean(),
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
                SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListLeavePolicies::route('/'),
            'create' => Pages\CreateLeavePolicy::route('/create'),
            'edit' => Pages\EditLeavePolicy::route('/{record}/edit'),
        ];
    }
}
