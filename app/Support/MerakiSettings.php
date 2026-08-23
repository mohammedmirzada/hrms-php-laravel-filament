<?php

namespace App\Support;

/**
 * Settings the client changes themselves, from the Settings page.
 *
 * Kept in one small JSON file instead of a database table, so there is
 * nothing to migrate and nothing to back up separately. The file lives in
 * storage/, which a deploy never touches.
 */
class MerakiSettings {

    private const FILE = 'meraki-settings.json';

    /** Used until the client saves the Settings page for the first time. */
    private const DEFAULT_START = '08:00';
    private const DEFAULT_END   = '17:00';

    /** Shift start and end for one client, as 'HH:MM'. */
    public static function shift(string $client): array {

        $saved = self::all()[$client] ?? [];

        return [
            'start' => self::time($saved['shift_start'] ?? null) ?? self::DEFAULT_START,
            'end'   => self::time($saved['shift_end'] ?? null) ?? self::DEFAULT_END,
        ];
    }

    /** Shift length in minutes. An end before the start means a night shift. */
    public static function shiftMinutes(string $client): int {

        $shift = self::shift($client);

        $length = self::minutes($shift['end']) - self::minutes($shift['start']);

        return $length > 0 ? $length : $length + 1440;
    }

    /** Store a new shift. Returns false if either time is not HH:MM. */
    public static function saveShift(string $client, ?string $start, ?string $end): bool {

        $start = self::time($start);
        $end   = self::time($end);

        if ($start === null || $end === null) {
            return false;
        }

        $all = self::all();

        $all[$client] = [
            'shift_start' => $start,
            'shift_end'   => $end,
        ];

        return file_put_contents(
            self::path(),
            json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        ) !== false;
    }

    /**
     * Minutes as words a normal person reads without thinking:
     * 0 -> "0 min", 65 -> "1 hour 5 min", 120 -> "2 hours".
     */
    public static function readable(int $minutes): string {

        $hours = intdiv($minutes, 60);
        $rest  = $minutes % 60;

        if ($hours === 0) {
            return $rest . ' min';
        }

        $text = $hours . ($hours === 1 ? ' hour' : ' hours');

        return $rest > 0 ? $text . ' ' . $rest . ' min' : $text;
    }

    /** Minutes since midnight. */
    public static function minutes(string $time): int {

        [$hours, $mins] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours) * 60 + (int) $mins;
    }

    // ----------------------------------------------------------------- inside

    private static function all(): array {

        $path = self::path();

        if (! is_file($path)) {
            return [];
        }

        return (array) json_decode((string) file_get_contents($path), true);
    }

    private static function path(): string {

        return storage_path('app/' . self::FILE);
    }

    /** Accepts '8:00' or '08:00', gives back '08:00'. Null if unusable. */
    private static function time(?string $value): ?string {

        if (! is_string($value) || ! preg_match('/^(\d{1,2}):(\d{2})$/', trim($value), $m)) {
            return null;
        }

        if ((int) $m[1] > 23 || (int) $m[2] > 59) {
            return null;
        }

        return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
    }

}
