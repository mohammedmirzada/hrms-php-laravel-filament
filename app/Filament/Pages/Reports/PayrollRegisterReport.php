<?php

namespace App\Filament\Pages\Reports;

use App\Enums\SalaryItemType;
use App\Models\Employer;
use App\Models\EmployerCompensation;
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

class PayrollRegisterReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::Banknotes;
    protected static string|UnitEnum|null $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Payroll Register';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Monthly Payroll Register';

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
                        'compensations' => fn ($q) => $q->whereNull('effective_to')->with('salaryStructure.items'),
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
                    ->getStateUsing(fn ($record) => $this->getActiveCompensation($record)?->basic_salary ?? 0)
                    ->numeric(decimalPlaces: 2)
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->addSelect(['basic_salary_sort' => EmployerCompensation::select('basic_salary')
                            ->whereColumn('employer_id', 'employers.id')
                            ->whereNull('effective_to')
                            ->limit(1)])
                        ->orderBy('basic_salary_sort', $direction)),
                TextColumn::make('total_earnings')
                    ->label('Earnings')
                    ->getStateUsing(fn ($record) => $this->calculateEarnings($record))
                    ->numeric(decimalPlaces: 2)
                    ->color('success'),
                TextColumn::make('total_deductions')
                    ->label('Deductions')
                    ->getStateUsing(fn ($record) => $this->calculateDeductions($record))
                    ->numeric(decimalPlaces: 2)
                    ->color('danger'),
                TextColumn::make('ss_employee')
                    ->label('SS (Employee)')
                    ->getStateUsing(fn ($record) => $this->calculateSS($record, 'employee'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('ss_employer')
                    ->label('SS (Employer)')
                    ->getStateUsing(fn ($record) => $this->calculateSS($record, 'employer'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('net_pay')
                    ->label('Net Pay')
                    ->getStateUsing(fn ($record) => $this->calculateNetPay($record))
                    ->numeric(decimalPlaces: 2)
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('currency')
                    ->label('Currency')
                    ->getStateUsing(fn ($record) => $this->getActiveCompensation($record)?->currency_code ?? '—')
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

    private function getActiveCompensation(Employer $employer): ?EmployerCompensation
    {
        return $employer->compensations->first();
    }

    private function calculateEarnings(Employer $employer): float
    {
        $compensation = $this->getActiveCompensation($employer);
        if (! $compensation) return 0;

        $basic = (float) $compensation->basic_salary;
        $total = 0;

        foreach ($compensation->salaryStructure?->items ?? [] as $item) {
            if ($item->type === SalaryItemType::Earning) {
                $total += (float) $item->calculateAmount($basic);
            }
        }

        return $total;
    }

    private function calculateDeductions(Employer $employer): float
    {
        $compensation = $this->getActiveCompensation($employer);
        if (! $compensation) return 0;

        $basic = (float) $compensation->basic_salary;
        $total = 0;

        foreach ($compensation->salaryStructure?->items ?? [] as $item) {
            if ($item->type === SalaryItemType::Deduction) {
                $total += (float) $item->calculateAmount($basic);
            }
        }

        return $total;
    }

    private function calculateSS(Employer $employer, string $side): float
    {
        $compensation = $this->getActiveCompensation($employer);
        if (! $compensation) return 0;

        $rule = SocialSecurityRule::where('branch_id', $employer->branch_id)
            ->whereNull('effective_to')
            ->first();

        if (! $rule) return 0;

        $basic = (float) $compensation->basic_salary;
        $percent = $side === 'employee' ? (float) $rule->employee_percent : (float) $rule->employer_percent;

        $amount = $basic * $percent / 100;

        if ($rule->cap_enabled && $amount > (float) $rule->cap_amount) {
            $amount = (float) $rule->cap_amount;
        }

        return $amount;
    }

    private function calculateNetPay(Employer $employer): float
    {
        $compensation = $this->getActiveCompensation($employer);
        if (! $compensation) return 0;

        $basic = (float) $compensation->basic_salary;
        $earnings = $this->calculateEarnings($employer);
        $deductions = $this->calculateDeductions($employer);
        $ssEmployee = $this->calculateSS($employer, 'employee');

        return $earnings - $deductions - $ssEmployee;
    }
}
