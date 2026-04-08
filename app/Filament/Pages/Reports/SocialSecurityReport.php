<?php

namespace App\Filament\Pages\Reports;

use App\Models\Employer;
use App\Models\SocialSecurityRule;
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

class SocialSecurityReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ShieldCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Social Security';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Social Security Contribution Report';

    protected string $view = 'filament.pages.reports.report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employer::query()
                    ->whereHas('compensations', fn (Builder $q) => $q->whereNull('effective_to'))
                    ->with([
                        'department',
                        'branch',
                        'compensations' => fn ($q) => $q->whereNull('effective_to'),
                    ])
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
                TextColumn::make('basic_salary')
                    ->label('Basic Salary')
                    ->getStateUsing(fn ($record) => $record->compensations->first()?->basic_salary ?? 0)
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('ss_base')
                    ->label('SS Base')
                    ->getStateUsing(fn ($record) => $this->getSSBase($record))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('employee_pct')
                    ->label('Employee %')
                    ->getStateUsing(fn ($record) => $this->getRule($record)?->employee_percent ?? '—')
                    ->suffix('%'),
                TextColumn::make('employer_pct')
                    ->label('Employer %')
                    ->getStateUsing(fn ($record) => $this->getRule($record)?->employer_percent ?? '—')
                    ->suffix('%'),
                TextColumn::make('employee_contribution')
                    ->label('Employee SS')
                    ->getStateUsing(fn ($record) => $this->calculateContribution($record, 'employee'))
                    ->numeric(decimalPlaces: 2)
                    ->color('danger'),
                TextColumn::make('employer_contribution')
                    ->label('Employer SS')
                    ->getStateUsing(fn ($record) => $this->calculateContribution($record, 'employer'))
                    ->numeric(decimalPlaces: 2)
                    ->color('warning'),
                TextColumn::make('total_contribution')
                    ->label('Total')
                    ->getStateUsing(fn ($record) => $this->calculateContribution($record, 'employee') + $this->calculateContribution($record, 'employer'))
                    ->numeric(decimalPlaces: 2)
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('currency')
                    ->label('Currency')
                    ->getStateUsing(fn ($record) => $this->getRule($record)?->currency_code ?? $record->compensations->first()?->currency_code ?? '—')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->native(false)
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('id');
    }

    private function getRule(Employer $employer): ?SocialSecurityRule
    {
        return SocialSecurityRule::where('branch_id', $employer->branch_id)
            ->whereNull('effective_to')
            ->first();
    }

    private function getSSBase(Employer $employer): float
    {
        $compensation = $employer->compensations->first();
        if (! $compensation) return 0;

        return (float) $compensation->basic_salary;
    }

    private function calculateContribution(Employer $employer, string $side): float
    {
        $rule = $this->getRule($employer);
        if (! $rule) return 0;

        $base = $this->getSSBase($employer);
        $percent = $side === 'employee' ? (float) $rule->employee_percent : (float) $rule->employer_percent;

        $amount = $base * $percent / 100;

        if ($rule->cap_enabled && $amount > (float) $rule->cap_amount) {
            $amount = (float) $rule->cap_amount;
        }

        return $amount;
    }
}
