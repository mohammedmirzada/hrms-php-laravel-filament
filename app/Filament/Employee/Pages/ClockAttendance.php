<?php

namespace App\Filament\Employee\Pages;

use App\Enums\AttendanceEventSource;
use App\Enums\AttendanceEventType;
use App\Models\AttendanceEvent;
use App\Models\Employer;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ClockAttendance extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.employee.pages.clock-attendance';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::Clock;
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Clock Attendance';
    protected static ?string $navigationLabel = 'Clock In / Out';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    private function getEmployee(): Employer
    {
        return auth()->guard('employer')->user();
    }

    private function getLastEvent(): ?AttendanceEvent
    {
        return AttendanceEvent::where('employer_id', $this->getEmployee()->id)
            ->whereDate('event_at', today())
            ->where('is_valid', true)
            ->latest('event_at')
            ->first();
    }

    public function isClockedIn(): bool
    {
        $last = $this->getLastEvent();

        return $last && $last->event_type === AttendanceEventType::In->value;
    }

    public function getTodayEvents()
    {
        return AttendanceEvent::where('employer_id', $this->getEmployee()->id)
            ->whereDate('event_at', today())
            ->where('is_valid', true)
            ->orderBy('event_at')
            ->get();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                FileUpload::make('selfie')
                    ->label('Selfie')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('attendance-selfies')
                    ->maxSize(5120)
                    ->extraInputAttributes(['capture' => 'user'])
                    ->required()
                    ->helperText('Take a selfie to confirm your attendance.'),
            ])
            ->statePath('data');
    }

    public function clock(): void
    {
        $this->form->validate();

        $employee = $this->getEmployee();
        $nextType = $this->isClockedIn()
            ? AttendanceEventType::Out
            : AttendanceEventType::In;

        $data = $this->form->getState();

        AttendanceEvent::create([
            'employer_id' => $employee->id,
            'branch_id'   => $employee->branch_id,
            'source'      => AttendanceEventSource::Mobile->value,
            'event_type'  => $nextType->value,
            'event_at'    => now(),
            'selfie_path' => $data['selfie'] ?? null,
        ]);

        $this->form->fill();

        Notification::make()
            ->title($nextType === AttendanceEventType::In ? 'Clocked In' : 'Clocked Out')
            ->body('Attendance recorded at ' . now()->format('h:i A'))
            ->success()
            ->send();
    }
}
