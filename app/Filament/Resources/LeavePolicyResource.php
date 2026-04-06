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
                    ->description('How does this employee earn leave? Turn on accrual and the system will automatically give employees their leave balance based on the rules below. No manual work needed.')
                    ->schema([
                        Toggle::make('accrual_enabled')
                            ->label('Enable Accrual')
                            ->reactive()
                            ->columnSpanFull()
                            ->helperText('Turn on so employees automatically get a leave balance. Turn off for leave types with no balance limit, like unpaid leave.'),
                        TextInput::make('accrual_rate')
                            ->numeric()
                            ->nullable()
                            ->required(fn ($get) => $get('accrual_enabled'))
                            ->helperText('How many days (or hours) the employee gets. e.g. 21 = 21 days per year.'),
                        Select::make('accrual_unit')
                            ->native(false)
                            ->options(LeaveAccrualUnit::labels())
                            ->nullable()
                            ->required(fn ($get) => $get('accrual_enabled'))
                            ->helperText('"Days per Year" gives all days at once every year. "Days per Month" gives a small amount each month — better for new hires joining mid-year.'),
                        Select::make('accrual_start_rule')
                            ->native(false)
                            ->options(LeaveAccrualStartRule::labels())
                            ->nullable()
                            ->required(fn ($get) => $get('accrual_enabled'))
                            ->live()
                            ->helperText('When does the employee start earning leave? From their hire date, after their probation ends, or on a fixed date every year (e.g. Jan 1).'),
                        TextInput::make('accrual_start_month_day')
                            ->label('Fixed Start Date (MM-DD)')
                            ->placeholder('01-01')
                            ->maxLength(5)
                            ->nullable()
                            ->required(fn ($get) => $get('accrual_start_rule') === 'FIXED_DATE')
                            ->rules(['nullable', 'regex:/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])/'])
                            ->helperText('The date leave resets every year for all employees. Format: MM-DD. e.g. 01-01 = every January 1st.'),
                        TextInput::make('annual_cap')
                            ->label('Annual Cap (days)')
                            ->numeric()
                            ->nullable()
                            ->helperText('The most leave an employee can earn in one year. Leave empty for no limit.'),
                    ])
                    ->columns(3),

                Section::make('Carryover Settings')
                    ->compact()
                    ->description('What happens to unused leave at year-end? By default it expires. Turn on carryover to let employees keep some or all of it for next year.')
                    ->schema([
                        Toggle::make('carryover_enabled')
                            ->label('Enable Carryover')
                            ->reactive()
                            ->helperText('Off = unused leave expires at year-end. On = unused leave rolls over to next year.'),
                        TextInput::make('carryover_cap')
                            ->label('Carryover Cap (days)')
                            ->numeric()
                            ->nullable()
                            ->helperText('Max days that can roll over. e.g. 5 = only 5 days carry over, the rest expire. Leave empty to carry over everything.'),
                        TextInput::make('carryover_expiry_date')
                            ->label('Carried Leave Expires On (MM-DD)')
                            ->placeholder('03-31')
                            ->nullable()
                            ->rules(['nullable', 'regex:/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/'])
                            ->helperText('Carried days expire on this date. e.g. 03-31 = employees must use them before March 31st. Leave empty if carried leave never expires.'),
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
                            ->default(0)
                            ->minValue(0)
                            ->helperText('The shortest leave request allowed, in minutes. E.g. 60 = no requests under 1 hour. 480 = must take at least a full 8-hour day. Set to 0 for no minimum.'),
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
