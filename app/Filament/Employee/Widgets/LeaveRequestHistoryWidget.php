<?php

namespace App\Filament\Employee\Widgets;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

class LeaveRequestHistoryWidget extends TableWidget {

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected int $employerId;

    public function mount(): void
    {
        $this->employerId = Auth::guard('employer')->id();
    }

    public function table(Table $table): Table {
        return $table
            ->heading('Leave Request History')
            ->query(
                LeaveRequest::query()
                    ->with('leaveType')
                    ->where('employer_id', $this->employerId)
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('leaveType.name')
                    ->label('Type')
                    ->formatStateUsing(fn ($record) =>
                        $record->leaveType?->getTranslation('name', app()->getLocale())
                        ?: $record->leaveType?->getTranslation('name', 'en')
                    ),
                TextColumn::make('start_at')
                    ->label('From')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('To')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('Days')
                    ->suffix(' days')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => LeaveRequestStatus::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state) => LeaveRequestStatus::tryFrom($state)?->label() ?? $state),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->date('M d, Y')
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([5, 10, 25])
            ->emptyStateHeading('No leave requests yet')
            ->emptyStateDescription('Your submitted leave requests will appear here.')
            ->emptyStateIcon(Heroicon::DocumentText);
    }
}
