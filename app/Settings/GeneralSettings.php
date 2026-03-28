<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings {

    public string $default_currency = 'USD';
    public string $default_language = 'en';
    public array $shortcuts = [""];

    public static function group(): string
    {
        return 'general';
    }

    public static function getDefaultCurrency(): string{
        return app(GeneralSettings::class)->default_currency;
    }

    public static function getDefaultLanguage(): string{
        return app(GeneralSettings::class)->default_language;
    }

    public static function getShortcuts(): array{
        return app(GeneralSettings::class)->shortcuts;
    }

}