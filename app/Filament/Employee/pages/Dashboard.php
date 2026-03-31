<?php

namespace App\Filament\Employee\Pages;

use App\Filament\Employee\Widgets\LeaveBalanceWidget;
use App\Filament\Employee\Widgets\LeaveRequestHistoryWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page {

    protected string $view = 'filament.employee.pages.dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::Home;

    public function getWidgets(): array
    {
        return [
            LeaveBalanceWidget::class,
            LeaveRequestHistoryWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
