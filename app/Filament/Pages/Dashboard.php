<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Dashboard extends Page {

    protected string $view = 'filament.pages.dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::Home;
    protected static string|UnitEnum|null $navigationGroup = 'Main';

    use HasPageShield;
    
}
