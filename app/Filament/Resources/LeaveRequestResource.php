<?php

namespace App\Filament\Resources;

use App\Enums\LeaveRequestStatus;
use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Filament\Resources\LeaveRequestResource\RelationManagers;
use App\Models\Branch;
use App\Models\Employer;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::DocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Leave Management';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Request Details')
                    ->schema([
                        Select::make('employer_id')
                            ->native(false)
                            ->label('Employee')
                            ->relationship('employer', 'full_name')
                            ->getOptionLabelFromRecordUsing(fn (Employer $record) => $record->getTranslation('full_name', 'en'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('The employee who is requesting time off.'),
                        Select::make('branch_id')
                            ->native(false)
                            ->label('Branch')
                            ->relationship('branch', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Branch $record) => $record->getTranslation('name', 'en'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('The branch the employee belongs to. Leave policies are branch-specific.'),
                        Select::make('leave_type_id')
                            ->native(false)
                            ->label('Leave Type')
                            ->relationship('leaveType', 'name')
                            ->getOptionLabelFromRecordUsing(fn (LeaveType $record) => $record->getTranslation('name', 'en'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('The category of leave being requested (e.g. Annual, Sick, Maternity).'),
                        Select::make('policy_id')
                            ->native(false)
                            ->label('Leave Policy')
                            ->relationship('policy', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "Policy #{$record->id} — " . ($record->branch?->getTranslation('name', 'en') ?? ''))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('The rule set that controls approval steps, limits, and accrual for this request. Leave empty to use the default policy for this branch and leave type.'),
                    ])
                    ->columns(2),

                Section::make('Duration')
                    ->schema([
                        Select::make('day_part')
                            ->native(false)
                            ->options([
                                'FULL_DAY' => 'Full Day',
                                'HALF_DAY_AM' => 'Half Day (AM)',
                                'HALF_DAY_PM' => 'Half Day (PM)',
                                'HOURLY' => 'Hourly',
                            ])
                            ->required()
                            ->helperText('Full Day = 8 hrs per day. Half Day AM/PM = 4 hrs. Hourly = exact hours between start and end times. This affects the calculated duration below.')
                            ->live()
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $result = self::calculateDuration($get('start_at'), $get('end_at'), $get('day_part'));
                                $set('duration_minutes', $result['minutes']);
                                $set('duration_days', $result['days']);
                            }),
                        DateTimePicker::make('start_at')
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $result = self::calculateDuration($get('start_at'), $get('end_at'), $get('day_part'));
                                $set('duration_minutes', $result['minutes']);
                                $set('duration_days', $result['days']);
                            }),
                        DateTimePicker::make('end_at')
                            ->native(false)
                            ->required()
                            ->after('start_at')
                            ->live()
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $result = self::calculateDuration($get('start_at'), $get('end_at'), $get('day_part'));
                                $set('duration_minutes', $result['minutes']);
                                $set('duration_days', $result['days']);
                            }),
                        TextEntry::make('duration_display')
                            ->label('Calculated Duration')
                            ->state(function (callable $get): string {
                                $minutes = $get('duration_minutes');
                                $days = $get('duration_days');
                                if ($minutes === null) {
                                    return 'Select dates and day part to calculate';
                                }
                                return "{$minutes} minutes ({$days} days)";
                            }),
                        \Filament\Forms\Components\Hidden::make('duration_minutes'),
                        \Filament\Forms\Components\Hidden::make('duration_days'),
                    ])
                    ->columns(2),

                Section::make('Details')
                    ->schema([
                        Textarea::make('reason')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('attachment_path')
                            ->label('Attachment')
                            ->directory('leave-attachments')
                            ->maxSize(5120)
                            ->nullable()
                            ->openable(),
                        Select::make('status')
                            ->native(false)
                            ->options(LeaveRequestStatus::labels())
                            ->disableOptionWhen(function (string $value, $record): bool {
                                if (! $record) {
                                    return false;
                                }

                                $current = $record->status;
                                $allowed = LeaveRequestStatus::transitions()[$current] ?? [];

                                return $value !== $current && ! in_array($value, $allowed);
                            })
                            ->default(LeaveRequestStatus::Draft->value)
                            ->required()
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['employer', 'leaveType', 'branch']))
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('employer.full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->employer?->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('leaveType.name')
                    ->label('Type')
                    ->formatStateUsing(fn ($record) => $record->leaveType?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('start_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('Days')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => LeaveRequestStatus::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state) => LeaveRequestStatus::tryFrom($state)?->label() ?? $state)
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(LeaveRequestStatus::labels())
                    ->searchable()
                    ->native(false),
                SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name')
                    ->searchable()
                    ->native(false)
                    ->preload(),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->native(false)
                    ->preload(),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function calculateDuration(?string $startAt, ?string $endAt, ?string $dayPart): array
    {
        if (! $startAt || ! $endAt || ! $dayPart) {
            return ['minutes' => null, 'days' => null];
        }

        $start = Carbon::parse($startAt);
        $end = Carbon::parse($endAt);

        if ($end->lte($start)) {
            return ['minutes' => null, 'days' => null];
        }

        $totalMinutes = match ($dayPart) {
            'HOURLY' => (int) $start->diffInMinutes($end),
            'HALF_DAY_AM', 'HALF_DAY_PM' => 240 * max(1, $start->diffInDays($end)),
            'FULL_DAY' => 480 * max(1, (int) ceil($start->diffInDays($end))),
            default => (int) $start->diffInMinutes($end),
        };

        $days = round($totalMinutes / 480, 2);

        return ['minutes' => $totalMinutes, 'days' => $days];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ApprovalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
