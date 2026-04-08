<?php

namespace App\Filament\Pages\Reports;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\Employer;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LeaveUsageReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::CalendarDays;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Leave Usage';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Leave Usage / History Report';

    protected string $view = 'filament.pages.reports.report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LeaveRequest::query()
                    ->with(['employer', 'employer.department', 'branch', 'leaveType', 'approvals.actionByUser'])
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('employer.full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->employer?->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employer.department.name')
                    ->label('Department')
                    ->formatStateUsing(fn ($record) => $record->employer?->department?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->formatStateUsing(fn ($record) => $record->leaveType?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('start_at')
                    ->label('Start')
                    ->dateTime('Y-m-d')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('End')
                    ->dateTime('Y-m-d')
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('Days')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => LeaveRequestStatus::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn ($state) => LeaveRequestStatus::tryFrom($state)?->label() ?? $state)
                    ->sortable(),
                TextColumn::make('approved_by')
                    ->label('Approved By')
                    ->getStateUsing(function ($record) {
                        $lastApproval = $record->approvals
                            ->where('status', 'APPROVED')
                            ->sortByDesc('step')
                            ->first();
                        return $lastApproval?->actionByUser?->name ?? '—';
                    }),
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
                SelectFilter::make('status')
                    ->options(LeaveRequestStatus::labels())
                    ->native(false)
                    ->multiple()
                    ->searchable(),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('date_from')
                            ->native(false)
                            ->label('From'),
                        DatePicker::make('date_to')
                            ->native(false)
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['date_from'] ?? null) {
                            $query->whereDate('start_at', '>=', $data['date_from']);
                        }
                        if ($data['date_to'] ?? null) {
                            $query->whereDate('end_at', '<=', $data['date_to']);
                        }
                    }),
            ])
            ->defaultSort('start_at', 'desc');
    }
}
