<?php

namespace App\Filament\Resources;

use App\Enums\LeaveAccrualStartRule;
use App\Enums\LeaveAccrualUnit;
use App\Filament\Resources\LeavePolicyResource\Pages;
use App\Models\Branch;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use BackedEnum;
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

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ShieldExclamation;

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
                            ->nullable()
                            ->helperText('How much leave to add in each cycle. E.g. 1.5 with "Days per Month" means 1.5 days are added every month.'),
                        Select::make('accrual_unit')
                            ->native(false)
                            ->options(LeaveAccrualUnit::labels())
                            ->nullable()
                            ->helperText('How often and in what unit leave is added to employee balances.'),
                        Select::make('accrual_start_rule')
                            ->native(false)
                            ->options(LeaveAccrualStartRule::labels())
                            ->nullable()
                            ->helperText('When accrual begins for each employee. "After Probation" uses the employee\'s probation end date. "Fixed Date" uses the MM-DD field below.'),
                        TextInput::make('accrual_start_month_day')
                            ->label('Fixed Start (MM-DD)')
                            ->placeholder('01-01')
                            ->maxLength(5)
                            ->nullable()
                            ->rules(['nullable', 'regex:/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])/'])
                            ->helperText('Only required when "Fixed Date" is selected above. Format: MM-DD. E.g. 01-01 means accrual resets every January 1st for all employees under this policy.'),
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
                            ->nullable()
                            ->helperText('Maximum days an employee can carry to the next year. Leave empty for unlimited carryover.'),
                        TextInput::make('carryover_expiry_date')
                            ->label('Carryover Expiry (MM-DD)')
                            ->placeholder('03-31')
                            ->helperText('e.g. 03-31 for March 31st every year')
                            ->nullable()
                            ->rules(['nullable', 'regex:/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/']),
                    ])
                    ->columns(1),

                Section::make('Request Rules')
                    ->compact()
                    ->schema([
                        Toggle::make('allow_hourly')
                            ->label('Allow Hourly Requests')
                            ->helperText('If on, employees can submit leave in exact hours (e.g. 2 hours for a doctor visit). Requires the leave type unit to support hours.'),
                        Toggle::make('allow_half_day')
                            ->label('Allow Half-Day')
                            ->helperText('If on, employees can request just the morning (AM) or afternoon (PM) instead of a full day.'),
                        TextInput::make('min_request_unit_minutes')
                            ->label('Minimum Request (minutes)')
                            ->numeric()
                            ->nullable()
                            ->helperText('The shortest leave request allowed, in minutes. E.g. 60 = no requests under 1 hour. 480 = must take at least a full 8-hour day. Leave empty for no minimum.'),
                    ])
                    ->columns(3),

                Section::make('Approval Workflow')
                    ->compact()
                    ->schema([
                        Toggle::make('requires_manager_approval')
                            ->label('Manager Approval')
                            ->helperText('If on, the employee\'s direct manager must approve before the request moves forward.'),
                        Toggle::make('requires_hr_approval')
                            ->label('HR Approval')
                            ->helperText('If on, an HR user must approve after the manager. Can be used alone without manager approval.'),
                        Toggle::make('requires_final_approval')
                            ->label('Final Approval')
                            ->helperText('If on, a final sign-off is required (e.g. by a director or CEO) after HR approval. Enable only if your company has a three-step approval process.'),
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
