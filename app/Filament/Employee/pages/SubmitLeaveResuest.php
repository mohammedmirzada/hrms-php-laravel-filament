<?php

namespace App\Filament\Employee\Pages;

use App\Enums\LeaveRequestDayPart;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveUnit;
use App\Models\Employer;
use App\Models\LeavePolicy;
use App\Services\LeaveBalanceCalculator;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class SubmitLeaveResuest extends Page implements HasForms {

    use InteractsWithForms;

    protected string $view = 'filament.employee.pages.submit-leave-resuest';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::DocumentDuplicate;
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Submit Leave Request';
    protected static ?string $navigationLabel = 'Submit Leave Request';

    public ?array $data = [];

    public function mount(): void {
        $this->form->fill();
    }

    private function getEmployee(): Employer {
        return auth()->guard('employer')->user();
    }

    public function form(Schema $schema): Schema {
        $employee = $this->getEmployee();
        $branchId = $employee->branch_id;

        return $schema
            ->schema([
                Section::make('Leave Details')
                    ->schema([
                        Select::make('leave_type_id')
                            ->native(false)
                            ->label('Leave Type')
                            ->options(function () use ($branchId) {
                                return LeavePolicy::where('branch_id', $branchId)
                                    ->with('leaveType')
                                    ->get()
                                    ->pluck('leaveType')
                                    ->filter()
                                    ->mapWithKeys(fn (LeaveType $type) => [
                                        $type->id => $type->getTranslation('name', app()->getLocale())
                                            ?: $type->getTranslation('name', 'en'),
                                    ]);
                            })
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                $set('start_at', null);
                                $set('end_at', null);
                                $leaveType = LeaveType::find($get('leave_type_id'));
                                $set('day_part', $leaveType?->default_unit === 'HOUR'
                                    ? LeaveRequestDayPart::Hourly->value
                                    : LeaveRequestDayPart::FullDay->value
                                );
                            })
                            ->helperText('Only leave types available for your branch are shown.'),

                        TextEntry::make('leave_balance')
                            ->label('Your Balance')
                            ->state(function (callable $get) use ($employee) {
                                $leaveTypeId = $get('leave_type_id');
                                if (! $leaveTypeId) {
                                    return new HtmlString('<span class="text-gray-400">Select a leave type to see your balance.</span>');
                                }

                                $result  = app(LeaveBalanceCalculator::class)->getBalance($employee, $leaveTypeId);
                                $type    = \App\Models\LeaveType::find($leaveTypeId);
                                $isHour  = $type?->default_unit === 'HOUR';

                                if ($isHour) {
                                    $value = round($result['minutes'] / 60, 1);
                                    $unit  = 'hrs';
                                } else {
                                    $value = round($result['minutes'] / 480, 1);
                                    $unit  = 'days';
                                }

                                if ($result['accrued'] === 0) {
                                    return new HtmlString('<span class="text-gray-400">No leave policy accrual configured.</span>');
                                }

                                $color = $value > 0 ? 'text-success-600' : 'text-danger-600';
                                $label = ($value >= 0 ? '+' : '') . "{$value} {$unit} available";

                                return new HtmlString("<span class=\"font-semibold {$color}\">{$label}</span>");
                            }),

                        Select::make('day_part')
                            ->native(false)
                            ->label('Duration Type')
                            ->options(function (callable $get) use ($branchId) {
                                $leaveTypeId = $get('leave_type_id');

                                if (! $leaveTypeId) {
                                    return [LeaveRequestDayPart::FullDay->value => LeaveRequestDayPart::FullDay->label()];
                                }

                                $leaveType = LeaveType::find($leaveTypeId);
                                $policy = LeavePolicy::where('branch_id', $branchId)
                                    ->where('leave_type_id', $leaveTypeId)
                                    ->first();

                                $options = [];

                                if ($leaveType?->default_unit === LeaveUnit::Hour->value) {
                                    // Hour-based type: Hourly is always available
                                    $options[LeaveRequestDayPart::Hourly->value] = LeaveRequestDayPart::Hourly->label();
                                    if ($policy?->allow_half_day) {
                                        $options[LeaveRequestDayPart::HalfDayAm->value] = LeaveRequestDayPart::HalfDayAm->label();
                                        $options[LeaveRequestDayPart::HalfDayPm->value] = LeaveRequestDayPart::HalfDayPm->label();
                                    }
                                } else {
                                    // Day-based type: Full Day is always available
                                    $options[LeaveRequestDayPart::FullDay->value] = LeaveRequestDayPart::FullDay->label();
                                    if ($policy?->allow_half_day) {
                                        $options[LeaveRequestDayPart::HalfDayAm->value] = LeaveRequestDayPart::HalfDayAm->label();
                                        $options[LeaveRequestDayPart::HalfDayPm->value] = LeaveRequestDayPart::HalfDayPm->label();
                                    }
                                    if ($policy?->allow_hourly) {
                                        $options[LeaveRequestDayPart::Hourly->value] = LeaveRequestDayPart::Hourly->label();
                                    }
                                }

                                return $options;
                            })
                            ->required()
                            ->live()
                            ->helperText('Choose how your leave duration is measured.'),
                    ])
                    ->columns(2),

                Section::make('Period')
                    ->schema([
                        DatePicker::make('start_at')
                            ->native(false)
                            ->label('Start Date')
                            ->required()
                            ->live()
                            ->minDate(today()),

                        DatePicker::make('end_at')
                            ->native(false)
                            ->label('End Date')
                            ->required()
                            ->live()
                            ->minDate(today())
                            ->hidden(fn (callable $get) => $get('day_part') === LeaveRequestDayPart::Hourly->value),

                        TimePicker::make('start_time')
                            ->native(false)
                            ->label('Start Time')
                            ->seconds(false)
                            ->displayFormat('h:i A')
                            ->live()
                            ->required(fn (callable $get) => $get('day_part') === LeaveRequestDayPart::Hourly->value)
                            ->hidden(fn (callable $get) => $get('day_part') !== LeaveRequestDayPart::Hourly->value)
                            ->helperText('What time does your leave start?'),

                        TimePicker::make('end_time')
                            ->native(false)
                            ->label('End Time')
                            ->seconds(false)
                            ->displayFormat('h:i A')
                            ->live()
                            ->required(fn (callable $get) => $get('day_part') === LeaveRequestDayPart::Hourly->value)
                            ->hidden(fn (callable $get) => $get('day_part') !== LeaveRequestDayPart::Hourly->value)
                            ->helperText('What time does your leave end?'),

                        TextEntry::make('duration_preview')
                            ->label('Estimated Duration')
                            ->state(function (callable $get) {
                                $dayPart = $get('day_part');
                                $startDate = $get('start_at');

                                if ($dayPart === LeaveRequestDayPart::Hourly->value) {
                                    $startTime = $get('start_time');
                                    $endTime = $get('end_time');
                                    if (! $startDate || ! $startTime || ! $endTime) {
                                        return new HtmlString('<span class="text-gray-400">Fill in the date and times to see calculation.</span>');
                                    }
                                    $start = Carbon::parse("{$startDate} {$startTime}");
                                    $end = Carbon::parse("{$startDate} {$endTime}");
                                    $result = self::calculateDuration($start->toDateTimeString(), $end->toDateTimeString(), $dayPart);
                                } else {
                                    $result = self::calculateDuration($startDate, $get('end_at'), $dayPart);
                                }

                                if ($result['minutes'] === null) {
                                    return new HtmlString('<span class="text-gray-400">Fill in dates and duration type to see calculation.</span>');
                                }

                                $days = $result['days'];
                                if ($dayPart === LeaveRequestDayPart::Hourly->value) {
                                    $hours = round($result['minutes'] / 60, 1);
                                    $label = "{$hours} hours";
                                } else {
                                    $label = $days == 1 ? '1 day' : "{$days} days";
                                }

                                return new HtmlString("<span class=\"font-semibold\">{$label}</span>");
                            }),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Briefly explain why you need this leave.'),

                        FileUpload::make('attachment_path')
                            ->label(function (callable $get) {
                                $leaveType = LeaveType::find($get('leave_type_id'));
                                return $leaveType?->document_type
                                    ? "Required Document: {$leaveType->document_type}"
                                    : 'Attachment';
                            })
                            ->directory('leave-attachments')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->required(function (callable $get) {
                                $leaveType = LeaveType::find($get('leave_type_id'));
                                return (bool) $leaveType?->document_type;
                            })
                            ->hidden(function (callable $get) {
                                return ! $get('leave_type_id');
                            })
                            ->helperText(function (callable $get) {
                                $leaveType = LeaveType::find($get('leave_type_id'));
                                return $leaveType?->document_type
                                    ? "A {$leaveType->document_type} is required for this leave type. Upload a PDF or image (max 5 MB)."
                                    : 'Optional. Upload a supporting document if needed (max 5 MB).';
                            }),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void {
        $this->form->validate();

        $employee = $this->getEmployee();
        $data = $this->form->getState();

        $policy = LeavePolicy::where('branch_id', $employee->branch_id)
            ->where('leave_type_id', $data['leave_type_id'])
            ->first();

        if ($data['day_part'] === LeaveRequestDayPart::Hourly->value) {
            $startAt = Carbon::parse("{$data['start_at']} {$data['start_time']}");
            $endAt   = Carbon::parse("{$data['start_at']} {$data['end_time']}");
        } else {
            $startAt = Carbon::parse($data['start_at'])->startOfDay();
            $endAt   = Carbon::parse($data['end_at'])->endOfDay();
        }

        $duration = self::calculateDuration(
            $startAt->toDateTimeString(),
            $endAt->toDateTimeString(),
            $data['day_part']
        );

        if ($policy?->min_request_unit_minutes && $duration['minutes'] < $policy->min_request_unit_minutes) {
            $min = $policy->min_request_unit_minutes >= 60
                ? round($policy->min_request_unit_minutes / 60, 1) . ' hour(s)'
                : $policy->min_request_unit_minutes . ' minute(s)';

            throw ValidationException::withMessages([
                'data.end_time' => "The minimum request duration for this leave type is {$min}.",
            ]);
        }

        $balance = app(LeaveBalanceCalculator::class)->getBalance($employee, $data['leave_type_id']);

        if ($balance['accrued'] > 0 && $duration['minutes'] > $balance['minutes']) {
            $leaveType = LeaveType::find($data['leave_type_id']);
            $isHour    = $leaveType?->default_unit === 'HOUR';
            $available = $isHour
                ? round($balance['minutes'] / 60, 1) . ' hrs'
                : round($balance['minutes'] / 480, 1) . ' days';

            $field = $isHour ? 'data.end_time' : 'data.end_at';

            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => "You only have {$available} available. Reduce the duration of your request.",
            ]);
        }

        LeaveRequest::create([
            'employer_id'      => $employee->id,
            'branch_id'        => $employee->branch_id,
            'leave_type_id'    => $data['leave_type_id'],
            'policy_id'        => $policy?->id,
            'day_part'         => $data['day_part'],
            'start_at'         => $startAt,
            'end_at'           => $endAt,
            'duration_minutes' => $duration['minutes'],
            'duration_days'    => $duration['days'],
            'reason'           => $data['reason'] ?? null,
            'attachment_path'  => $data['attachment_path'] ?? null,
            'status'           => LeaveRequestStatus::Submitted->value,
        ]);

        $this->form->fill();

        Notification::make()
            ->title('Leave request submitted successfully.')
            ->body('You will be notified once it is reviewed.')
            ->success()
            ->send();
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
            'HALF_DAY_AM', 'HALF_DAY_PM' => 240 * max(1, (int) $start->diffInDays($end)),
            'FULL_DAY' => 480 * max(1, (int) ceil($start->diffInDays($end))),
            default => (int) $start->diffInMinutes($end),
        };

        $days = round($totalMinutes / 480, 2);

        return ['minutes' => $totalMinutes, 'days' => $days];
    }

}