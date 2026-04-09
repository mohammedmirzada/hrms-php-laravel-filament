<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.default_currency', 'USD');
        $this->migrator->add('general.shortcuts', []);
        $this->migrator->add('general.default_language', 'en');
    }
};