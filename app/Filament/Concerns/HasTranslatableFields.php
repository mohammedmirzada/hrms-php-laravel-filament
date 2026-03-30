<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

trait HasTranslatableFields
{
    protected static function getLocales(): array
    {
        return ['en', 'ar', 'ckb'];
    }

    protected static function getLocaleLabels(): array
    {
        return [
            'en' => 'English',
            'ar' => 'Arabic',
            'ckb' => 'Central Kurdish',
        ];
    }

    protected static function translatableTabs(string $field, string $label, bool $required = false, string $type = 'text'): Tabs
    {
        $locales = static::getLocales();
        $localeLabels = static::getLocaleLabels();

        return Tabs::make($label)
            ->schema(
                collect($locales)->map(function (string $locale) use ($field, $label, $required, $localeLabels, $type) {
                    $input = TextInput::make("{$field}.{$locale}")
                        ->label("{$label} ({$localeLabels[$locale]})")
                        ->required($required && $locale === 'en')
                        ->maxLength(255);

                    return Tab::make($localeLabels[$locale])
                        ->schema([$input]);
                })->all()
            )
            ->columnSpanFull();
    }
}
