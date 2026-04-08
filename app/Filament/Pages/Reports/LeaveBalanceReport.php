<?php

namespace App\Filament\Pages\Reports;

use App\Models\LeaveBalances;
use App\Models\Employer;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LeaveBalanceReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Scale;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Leave Balances';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Leave Balance Report';

    protected string $view = 'filament.pages.reports.report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LeaveBalances::query()
                    ->with(['employer', 'employer.department', 'branch', 'leaveType'])
            )
            ->columns([
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
                TextColumn::make('balance_days')
                    ->label('Balance (Days)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 3 ? 'warning' : 'success')),
                TextColumn::make('balance_minutes')
                    ->label('Balance (Hours)')
                    ->getStateUsing(fn ($record) => round($record->balance_minutes / 60, 1))
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                TextColumn::make('as_of')
                    ->label('As Of')
                    ->dateTime()
                    ->sortable(),
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
            ->defaultSort('employer_id');
    }
}
