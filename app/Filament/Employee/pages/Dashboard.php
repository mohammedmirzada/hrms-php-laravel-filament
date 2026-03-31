<?php

namespace App\Filament\Employee\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page {

    protected string $view = 'filament.employee.pages.dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::Home;
    
}
