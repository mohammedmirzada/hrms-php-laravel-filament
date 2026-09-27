<?php

namespace Database\Seeders;

use App\Models\Employer;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveDataSeeder extends Seeder
{
    /** One working day is eight hours. */
    private const MINUTES_PER_DAY = 480;

    public function __construct(private LeaveBalanceService $balances) {}

    public function run(): void
    {
        $types = LeaveType::all()->keyBy(fn (LeaveType $t) => $t->getTranslation('name', 'en'));
        $employers = Employer::all();

        $this->seedAccruals($employers, $types);
        $this->seedRequests($employers, $types);
    }

    /** Monthly accrual history, so every balance in the panel has a ledger behind it. */
    private function seedAccruals($employers, $types): void
    {
        $accruing = ['Annual Leave', 'Sick Leave', 'Hourly Permission'];
        $policies = LeavePolicy::all()->keyBy(fn (LeavePolicy $p) => $p->branch_id.':'.$p->leave_type_id);

        $entries = [];
        $touched = [];
        $startOfYear = Carbon::today()->startOfYear();

        foreach ($employers as $employer) {
            $hire = Carbon::parse($employer->hire_date->format('Y-m-d'));
            $from = $hire->gt($startOfYear) ? $hire->copy()->startOfMonth() : $startOfYear->copy();

            foreach ($accruing as $name) {
                $type = $types[$name] ?? null;
                $policy = $type ? ($policies[$employer->branch_id.':'.$type->id] ?? null) : null;

                if (! $policy || ! $policy->accrual_enabled) {
                    continue;
                }

                $perMonth = str_starts_with((string) ($policy->accrual_unit?->value ?? $policy->accrual_unit), 'HOUR')
                    ? (int) round((float) $policy->accrual_rate * 60)
                    : (int) round((float) $policy->accrual_rate * self::MINUTES_PER_DAY);

                for ($month = $from->copy(); $month->lte(Carbon::today()); $month->addMonth()) {
                    $entries[] = [
                        'employer_id' => $employer->id,
                        'branch_id' => $employer->branch_id,
                        'leave_type_id' => $type->id,
                        'leave_request_id' => null,
                        'entry_type' => 'ACCRUAL',
                        'amount_minutes' => $perMonth,
                        'occurred_on' => $month->copy()->endOfMonth()->min(Carbon::today())->toDateString(),
                        'note' => 'Monthly accrual for '.$month->format('F Y'),
                        'created_by' => null,
                        'updated_by' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $touched[] = [$employer->id, $employer->branch_id, $type->id];
            }

            // Annual leave carried over from last year, for anyone who was already on staff.
            $annual = $types['Annual Leave'] ?? null;
            $annualPolicy = $annual ? ($policies[$employer->branch_id.':'.$annual->id] ?? null) : null;

            if ($annual && $annualPolicy?->carryover_cap && $hire->lt($startOfYear)) {
                $entries[] = $this->entry(
                    $employer,
                    $annual->id,
                    'ADJUSTMENT',
                    (int) round((float) $annualPolicy->carryover_cap * self::MINUTES_PER_DAY),
                    $startOfYear->toDateString(),
                    'Carried over from '.$startOfYear->copy()->subYear()->year,
                );
            }

            // Types that are granted as a yearly entitlement instead of accruing.
            foreach (['Bereavement Leave', 'Marriage Leave', 'Maternity Leave'] as $name) {
                $type = $types[$name] ?? null;
                $policy = $type ? ($policies[$employer->branch_id.':'.$type->id] ?? null) : null;

                if (! $type || ! $policy || ! $policy->annual_cap) {
                    continue;
                }

                // Maternity leave only applies to the staff who can take it.
                if ($name === 'Maternity Leave' && $employer->genre->value !== 'female') {
                    continue;
                }

                $grantedOn = $hire->gt($startOfYear) ? $hire->toDateString() : $startOfYear->toDateString();

                $entries[] = $this->entry(
                    $employer,
                    $type->id,
                    'ADJUSTMENT',
                    (int) round((float) $policy->annual_cap * self::MINUTES_PER_DAY),
                    $grantedOn,
                    'Annual entitlement granted',
                );

                $touched[] = [$employer->id, $employer->branch_id, $type->id];
            }
        }

        foreach (array_chunk($entries, 500) as $chunk) {
            DB::table('leave_ledger_entries')->insert($chunk);
        }

        foreach ($touched as [$employerId, $branchId, $typeId]) {
            $this->balances->refreshBalance($employerId, $branchId, $typeId);
        }
    }

    /** Requests across every status, so each tab and badge in the panel is populated. */
    private function seedRequests($employers, $types): void
    {
        $hrUserId = User::where('email', 'hr@hrms.test')->value('id');
        $adminId = User::where('email', 'admin@hrms.test')->value('id');
        $policies = LeavePolicy::all()->keyBy(fn (LeavePolicy $p) => $p->branch_id.':'.$p->leave_type_id);

        // monthsBack of null means a date in the future.
        $templates = [
            ['type' => 'Annual Leave', 'months_back' => 5, 'day' => 12, 'days' => 5, 'final' => 'FINAL_APPROVED', 'reason' => 'Family trip to Sulaymaniyah'],
            ['type' => 'Sick Leave', 'months_back' => 3, 'day' => 8, 'days' => 2, 'final' => 'FINAL_APPROVED', 'reason' => 'Flu, medical note attached'],
            ['type' => 'Annual Leave', 'months_back' => 2, 'day' => 17, 'days' => 3, 'final' => 'REJECTED', 'reason' => 'Requested during month-end closing'],
            ['type' => 'Hourly Permission', 'months_back' => 1, 'day' => 21, 'hours' => 3, 'final' => 'FINAL_APPROVED', 'reason' => 'Bank appointment'],
            ['type' => 'Bereavement Leave', 'months_back' => 4, 'day' => 6, 'days' => 3, 'final' => 'FINAL_APPROVED', 'reason' => 'Death of a close relative'],
            ['type' => 'Annual Leave', 'months_back' => -1, 'day' => 9, 'days' => 4, 'final' => 'SUBMITTED', 'reason' => 'Eid holiday extension'],
            ['type' => 'Unpaid Leave', 'months_back' => -2, 'day' => 14, 'days' => 6, 'final' => 'DRAFT', 'reason' => 'Personal matters abroad'],
            ['type' => 'Marriage Leave', 'months_back' => -1, 'day' => 24, 'days' => 7, 'final' => 'MANAGER_APPROVED', 'reason' => 'Own marriage'],
        ];

        foreach ($employers as $employer) {
            $hire = Carbon::parse($employer->hire_date->format('Y-m-d'));

            // Each employee gets a different slice of the templates.
            $picked = array_filter(
                $templates,
                fn (array $t, int $i) => ($employer->id + $i) % 3 !== 0,
                ARRAY_FILTER_USE_BOTH
            );

            foreach ($picked as $template) {
                $type = $types[$template['type']] ?? null;

                if (! $type) {
                    continue;
                }

                $start = Carbon::today()
                    ->subMonths($template['months_back'])
                    ->startOfMonth()
                    ->addDays($template['day'] - 1);

                if ($start->lt($hire)) {
                    continue;
                }

                $isHourly = isset($template['hours']);

                if ($isHourly) {
                    $startAt = $start->copy()->setTime(10, 0);
                    $endAt = $startAt->copy()->addHours($template['hours']);
                    $minutes = $template['hours'] * 60;
                    $dayPart = 'HOURLY';
                } else {
                    $startAt = $start->copy()->setTime(8, 30);
                    $endAt = $start->copy()->addDays($template['days'] - 1)->setTime(16, 30);
                    $minutes = $template['days'] * self::MINUTES_PER_DAY;
                    $dayPart = 'FULL_DAY';
                }

                $request = LeaveRequest::create([
                    'employer_id' => $employer->id,
                    'branch_id' => $employer->branch_id,
                    'leave_type_id' => $type->id,
                    'policy_id' => $policies[$employer->branch_id.':'.$type->id]->id ?? null,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'duration_minutes' => $minutes,
                    'duration_days' => round($minutes / self::MINUTES_PER_DAY, 2),
                    'day_part' => $dayPart,
                    'reason' => $template['reason'],
                    'status' => 'DRAFT',
                ]);

                $this->advance($request, $template['final']);
                $this->recordApprovals($request, $template['final'], $hrUserId, $adminId);
            }
        }
    }

    /** One ledger row, ready for a bulk insert. */
    private function entry(Employer $employer, int $typeId, string $entryType, int $minutes, string $occurredOn, string $note): array
    {
        return [
            'employer_id' => $employer->id,
            'branch_id' => $employer->branch_id,
            'leave_type_id' => $typeId,
            'leave_request_id' => null,
            'entry_type' => $entryType,
            'amount_minutes' => $minutes,
            'occurred_on' => $occurredOn,
            'note' => $note,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /** Walk a request through the allowed transitions until it reaches its final status. */
    private function advance(LeaveRequest $request, string $target): void
    {
        $path = match ($target) {
            'DRAFT' => [],
            'SUBMITTED' => ['SUBMITTED'],
            'MANAGER_APPROVED' => ['SUBMITTED', 'MANAGER_APPROVED'],
            'HR_APPROVED' => ['SUBMITTED', 'MANAGER_APPROVED', 'HR_APPROVED'],
            'FINAL_APPROVED' => ['SUBMITTED', 'MANAGER_APPROVED', 'HR_APPROVED', 'FINAL_APPROVED'],
            'REJECTED' => ['SUBMITTED', 'MANAGER_APPROVED', 'REJECTED'],
            'CANCELLED' => ['SUBMITTED', 'CANCELLED'],
            default => [],
        };

        foreach ($path as $status) {
            $request->update(['status' => $status]);
        }
    }

    private function recordApprovals(LeaveRequest $request, string $target, ?int $hrUserId, ?int $adminId): void
    {
        if ($target === 'DRAFT') {
            return;
        }

        $steps = [
            ['step' => 1, 'role' => 'MANAGER', 'user' => $adminId],
            ['step' => 2, 'role' => 'HR', 'user' => $hrUserId],
            ['step' => 3, 'role' => 'FINAL', 'user' => $adminId],
        ];

        $reached = match ($target) {
            'SUBMITTED' => 0,
            'MANAGER_APPROVED' => 1,
            'HR_APPROVED' => 2,
            'FINAL_APPROVED' => 3,
            'REJECTED' => 1,
            default => 0,
        };

        foreach ($steps as $index => $step) {
            $position = $index + 1;

            $status = match (true) {
                $target === 'REJECTED' && $position === 2 => 'REJECTED',
                $target === 'REJECTED' && $position === 3 => 'SKIPPED',
                $position <= $reached => 'APPROVED',
                default => 'PENDING',
            };

            $request->approvals()->create([
                'step' => $step['step'],
                'role' => $step['role'],
                'assigned_to_user_id' => $step['user'],
                'status' => $status,
                'comment' => $status === 'REJECTED' ? 'Cannot approve during the closing period.' : null,
            ]);
        }
    }
}
