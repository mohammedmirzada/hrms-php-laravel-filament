<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GeneralSettingsPage extends SettingsPage {

    use HasPageShield;
    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;
    protected static string|UnitEnum|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'General';
    protected static ?int $navigationSort = 1;

    protected static string $settings = GeneralSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('default_currency')
                    ->native(false)
                    ->label('Default Currency')
                    ->options(config('currency'))
                    ->required()
                    ->searchable()
                    ->helperText('The system-wide fallback currency used in reports and displays when no specific currency is set elsewhere.'),
                Select::make('default_language')
                    ->native(false)
                    ->label('Default Language')
                    ->options(config('languages'))
                    ->required()
                    ->searchable()
                    ->helperText('The language used to display translated fields (employee names, departments, positions) when no specific language is requested.'),
                Repeater::make('shortcuts')
                    ->label('Dashboard Shortcuts')
                    ->schema([
                        Select::make('page')
                            ->required()
                            ->helperText('The name of the shortcut as it will appear on the dashboard.')
                            ->searchable()
                            ->native(false)
                            ->options(
                                collect(getFilamentResourcesAndPages())
                                    ->groupBy('group')
                                    ->map(fn ($items) => $items->pluck('label', 'route'))
                                    ->toArray()
                            )
                    ])
                    ->helperText('Add links to your most frequently used pages for quick access from the dashboard.'),
            ]);
    }
}
