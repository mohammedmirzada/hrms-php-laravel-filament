<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait HasActivityLogging
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return $this->defaultLogOptions();
    }

    protected function defaultLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])
            ->setDescriptionForEvent(fn (string $eventName) => class_basename(static::class)." was {$eventName}");
    }
}
