<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ExchangeRate;
use App\Models\PayrollPeriod;
use App\Models\SocialSecurityRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PayrollSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('email', 'admin@hrms.test')->value('id');

        $this->seedExchangeRates($adminId);
        $this->seedSocialSecurityRules();
        $this->seedPayrollPeriods();
    }

    private function seedExchangeRates(int $adminId): void
    {
        // One quote per month end for the last six months, plus today.
        $quotes = [
            'IQD' => 1310.00,
            'EUR' => 0.92,
            'TRY' => 34.15,
        ];

        $dates = collect(range(5, 0))
            ->map(fn (int $back) => Carbon::today()->subMonths($back)->endOfMonth())
            ->filter(fn (Carbon $date) => $date->lte(Carbon::today()))
            ->push(Carbon::today())
            ->unique(fn (Carbon $date) => $date->toDateString());

        foreach ($dates as $index => $date) {
            foreach ($quotes as $currency => $base) {
                // Nudge the rate a little each month so the history is not flat.
                $rate = round($base * (1 + (($index % 4) - 1) * 0.004), 8);

                ExchangeRate::create([
                    'base_code' => 'USD',
                    'quote_currency' => $currency,
                    'rate' => $rate,
                    'rate_date' => $date->toDateString(),
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]);
            }
        }
    }

    private function seedSocialSecurityRules(): void
    {
        // Iraqi social security: 12% employer / 5% employee on basic pay.
        foreach (Branch::all() as $branch) {
            SocialSecurityRule::create([
                'branch_id' => $branch->id,
                'employment_type' => null,
                'employer_percent' => 12.0000,
                'employee_percent' => 5.0000,
                'base_rule' => 'basic_only',
                'cap_enabled' => false,
                'cap_amount' => null,
                'currency_code' => 'USD',
                'effective_from' => '2026-01-01',
                'effective_to' => null,
            ]);
        }

        // Contract staff at head office are capped, to show the cap fields in use.
        $erbil = Branch::all()->first(fn (Branch $b) => $b->getTranslation('name', 'en') === 'Erbil Head Office');

        if ($erbil) {
            SocialSecurityRule::create([
                'branch_id' => $erbil->id,
                'employment_type' => 'contract',
                'employer_percent' => 12.0000,
                'employee_percent' => 5.0000,
                'base_rule' => 'gross',
                'cap_enabled' => true,
                'cap_amount' => 2500.0000,
                'currency_code' => 'USD',
                'effective_from' => '2026-01-01',
                'effective_to' => null,
            ]);
        }
    }

    private function seedPayrollPeriods(): void
    {
        foreach (Branch::all() as $branch) {
            // Three closed months, then the month that is still open.
            for ($back = 3; $back >= 0; $back--) {
                $start = Carbon::today()->subMonths($back)->startOfMonth();
                $end = $start->copy()->endOfMonth();

                $status = match ($back) {
                    3, 2 => 'approved',
                    1 => 'calculated',
                    default => 'open',
                };

                PayrollPeriod::create([
                    'branch_id' => $branch->id,
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    // The Baghdad payroll is processed in local currency, the rest in USD.
                    'processing_currency_code' => $branch->getTranslation('name', 'en') === 'Baghdad Branch' ? 'IQD' : 'USD',
                    'exchange_rate_date' => null,
                    'status' => $status,
                    'approved_by_user_id' => $status === 'approved' ? User::where('email', 'admin@hrms.test')->value('id') : null,
                    'approved_at' => $status === 'approved' ? $end->copy()->addDays(3) : null,
                    'immutable' => $status === 'approved',
                ]);
            }
        }
    }
}
