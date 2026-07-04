<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HomeStatic extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;
    protected static ?string $title = '';

    protected function getStats(): array
    {
        return [
            Stat::make('Brnaches', number_format(Branch::count()))->icon('heroicon-s-building-office'),
            Stat::make('Departments', number_format(Department::count()))->icon('heroicon-s-rectangle-group'),
            Stat::make('Employees', number_format(Employer::count()))->icon('heroicon-s-user-group'),
        ];
    }

    protected function getHeading(): ?string
    {
        return 'Overview';
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 3,
            'xl' => 3,
        ];
    }
}
