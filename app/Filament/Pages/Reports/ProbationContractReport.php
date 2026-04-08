<?php

namespace App\Filament\Pages\Reports;

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
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProbationContractReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Clock;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Probation & Contracts';
    protected static ?int $navigationSort = 8;
    protected static ?string $title = 'Probation & Contract Expiry Report';

    protected string $view = 'filament.pages.reports.report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employer::query()
                    ->where(function (Builder $q) {
                        $q->whereNotNull('probation_period_end_date')
                          ->orWhereNotNull('contract_expiry_date');
                    })
                    ->with(['department', 'position', 'branch', 'employmentStatus'])
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->formatStateUsing(fn ($record) => $record->department?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->formatStateUsing(fn ($record) => $record->branch?->getTranslation('name', 'en'))
                    ->sortable(),
                TextColumn::make('hire_date')
                    ->label('Hire Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('probation_period_end_date')
                    ->label('Probation Ends')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => match (true) {
                        ! $record->probation_period_end_date => null,
                        $record->probation_period_end_date->isPast() => 'danger',
                        $record->probation_period_end_date->diffInDays(now()) <= 30 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('probation_days_remaining')
                    ->label('Probation Days Left')
                    ->getStateUsing(function ($record) {
                        if (! $record->probation_period_end_date) return '—';
                        $days = (int) now()->startOfDay()->diffInDays($record->probation_period_end_date, false);
                        return $days;
                    })
                    ->color(fn ($state) => match (true) {
                        $state === '—' => null,
                        $state < 0 => 'danger',
                        $state <= 30 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('contract_expiry_date')
                    ->label('Contract Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => match (true) {
                        ! $record->contract_expiry_date => null,
                        $record->contract_expiry_date->isPast() => 'danger',
                        $record->contract_expiry_date->diffInDays(now()) <= 30 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('contract_days_remaining')
                    ->label('Contract Days Left')
                    ->getStateUsing(function ($record) {
                        if (! $record->contract_expiry_date) return '—';
                        $days = (int) now()->startOfDay()->diffInDays($record->contract_expiry_date, false);
                        return $days;
                    })
                    ->color(fn ($state) => match (true) {
                        $state === '—' => null,
                        $state < 0 => 'danger',
                        $state <= 30 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('employmentStatus.name')
                    ->label('Status')
                    ->formatStateUsing(fn ($record) => $record->employmentStatus?->getTranslation('name', 'en'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'probation' => 'Probation',
                        'contract' => 'Contract',
                    ])
                    ->native(false)
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'probation' => $query->whereNotNull('probation_period_end_date'),
                        'contract' => $query->whereNotNull('contract_expiry_date'),
                        default => $query,
                    }),
                SelectFilter::make('window')
                    ->label('Expiry Window')
                    ->options([
                        '30' => 'Next 30 Days',
                        '60' => 'Next 60 Days',
                        '90' => 'Next 90 Days',
                    ])
                    ->native(false)
                    ->query(function (Builder $query, array $data) {
                        $days = (int) ($data['value'] ?? 0);
                        if ($days <= 0) return;

                        $until = now()->addDays($days);
                        $query->where(function (Builder $q) use ($until) {
                            $q->where(fn (Builder $sub) => $sub
                                ->whereNotNull('probation_period_end_date')
                                ->where('probation_period_end_date', '<=', $until)
                            )->orWhere(fn (Builder $sub) => $sub
                                ->whereNotNull('contract_expiry_date')
                                ->where('contract_expiry_date', '<=', $until)
                            );
                        });
                    }),
            ])
            ->defaultSort('contract_expiry_date', 'asc');
    }
}
