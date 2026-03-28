<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::Home;
    protected static string|UnitEnum|null $navigationGroup = 'Main';

    use HasPageShield;

    public function getShortcuts(): array {
        
        /** @noinspection PhpUndefinedFunctionInspection */
        $all   = \getFilamentResourcesAndPages();
        $saved = app(GeneralSettings::class)->shortcuts;

        return collect($saved)
            ->filter(fn($item) => isset($item['page'], $all[$item['page']]))
            ->map(fn($item)    => $all[$item['page']])
            ->values()
            ->toArray();
    }
}
