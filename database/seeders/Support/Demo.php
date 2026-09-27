<?php

namespace Database\Seeders\Support;

/**
 * Small helpers shared by the demo seeders.
 */
class Demo
{
    /** Build a translatable value for the three locales the panel ships with. */
    public static function t(string $en, string $ckb, string $ar): array
    {
        return ['en' => $en, 'ckb' => $ckb, 'ar' => $ar];
    }

    /** Pick a deterministic item so re-seeding produces the same demo data. */
    public static function pick(array $items, int $seed): mixed
    {
        return $items[$seed % count($items)];
    }
}
