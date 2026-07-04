<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Currency is no longer used anywhere (payroll/salary/exchange were removed),
     * so drop the stored general.default_currency setting. Safe on fresh DBs too.
     */
    public function up(): void
    {
        $this->migrator->deleteIfExists('general.default_currency');
    }
};
