<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GeneralSettingsPage extends SettingsPage
{
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
                    ->searchable(),
                Select::make('default_language')
                    ->native(false)
                    ->label('Default Language')
                    ->options(config('languages'))
                    ->required()
                    ->searchable(),
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
