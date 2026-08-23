<?php

namespace App\Support;

/**
 * Settings the client changes themselves, from the Settings page.
 *
 * Kept in one small JSON file instead of a database table, so there is
 * nothing to migrate and nothing to back up separately. The file lives in
 * storage/, which a deploy never touches.
 *
 *   {
 *     "meraki": {
 *       "shifts": {
 *         "1": { "name": "Day shift",   "start": "08:00", "end": "17:00", "grace": 5 },
 *         "2": { "name": "Night shift", "start": "20:00", "end": "05:00", "grace": 5 }
 *       },
 *       "people": { "101": "2", "102": "1" }
 *     }
 *   }
 *
 * Shifts are never deleted, so an id always keeps pointing at the same shift.
 * Anybody not listed in "people" is on the first shift.
 */
class MerakiSettings {

    private const FILE = 'meraki-settings.json';

    /** Used until the client saves the Settings page for the first time. */
    private const DEFAULT_NAME  = 'Day shift';
    private const DEFAULT_START = '08:00';
    private const DEFAULT_END   = '17:00';
    private const DEFAULT_GRACE = 5;

    private const MAX_NAME  = 40;
    private const MAX_GRACE = 240;

    // ---------------------------------------------------------------- reading

    /**
     * Every shift, id => details. Never empty — there is always at least the
     * default one, so nothing downstream has to handle "no shift".
     *
     * Each row: id, name, start, end, grace, minutes, text.
     */
    public static function shifts(string $client): array {

        $saved  = self::all()[$client] ?? [];
        $shifts = [];

        foreach ((array) ($saved['shifts'] ?? []) as $id => $row) {
            $shifts[(string) $id] = self::build((string) $id, (array) $row);
        }

        // Before shifts had names there was one pair of times for everybody.
        // Read that as the first shift, so an old file loses nothing.
        if ($shifts === [] && isset($saved['shift_start'], $saved['shift_end'])) {
            $shifts['1'] = self::build('1', [
                'name'  => self::DEFAULT_NAME,
                'start' => $saved['shift_start'],
                'end'   => $saved['shift_end'],
                'grace' => self::DEFAULT_GRACE,
            ]);
        }

        if ($shifts === []) {
            $shifts['1'] = self::build('1', ['grace' => self::DEFAULT_GRACE]);
        }

        return $shifts;
    }

    /**
     * One shift. An unknown or missing id gives the first one, so a person
     * whose shift was never set still gets sensible numbers.
     */
    public static function shift(string $client, ?string $id = null): array {

        $shifts = self::shifts($client);

        return $shifts[(string) $id] ?? reset($shifts);
    }

    /** The shift a person is on. */
    public static function shiftFor(string $client, string $pin): array {

        return self::shift($client, self::people($client)[$pin] ?? null);
    }

    /**
     * Shift length in minutes. A shift is only ever a number of hours — an end
     * before the start simply wraps past midnight, and nothing else about it is
     * treated differently.
     */
    public static function shiftMinutes(string $client, ?string $id = null): int {

        return self::shift($client, $id)['minutes'];
    }

    /**
     * pin => shift id, for everybody who has been put on one. Ids that no
     * longer exist are dropped, so a bad file cannot hide someone's hours.
     */
    public static function people(string $client): array {

        $saved  = (array) ((self::all()[$client] ?? [])['people'] ?? []);
        $shifts = self::shifts($client);
        $out    = [];

        foreach ($saved as $pin => $id) {

            $id = (string) $id;

            if (isset($shifts[$id])) {
                $out[(string) $pin] = $id;
            }
        }

        return $out;
    }

    // ---------------------------------------------------------------- writing

    /**
     * Save the whole list of shifts in one go — the Settings page sends every
     * row at once, changed or not, plus any blank ones at the bottom.
     *
     * A row with an id it knows can only be **renamed**. Its hours are fixed
     * for good the moment it is created: punches are stamped with the shift
     * they were made under, and changing 08:00-17:00 into 08:00-13:00 would
     * silently rewrite every report that ever used it. To change hours, add a
     * new shift and move people onto it.
     *
     * A row with a name and no id is added. A row nobody typed anything into is
     * skipped. Nothing is ever removed: a shift left out of the list stays
     * exactly as it was.
     *
     * All or nothing — one bad row saves none of them, so the page can hand
     * back everything the user typed instead of half applying it.
     */
    public static function saveShifts(string $client, array $rows): bool {

        $shifts = self::shifts($client);

        // Ids only ever go up. Nothing is deleted, so one is never reused.
        $next = max(array_map('intval', array_keys($shifts))) + 1;

        foreach ($rows as $row) {

            $row = (array) $row;

            $id    = trim((string) ($row['id'] ?? ''));
            $name  = trim((string) ($row['name'] ?? ''));
            $start = trim((string) ($row['start'] ?? ''));
            $end   = trim((string) ($row['end'] ?? ''));

            // The empty row at the bottom, left alone.
            if ($id === '' && $name === '' && $start === '' && $end === '') {
                continue;
            }

            // An existing shift: the name is all that can move. Whatever the
            // form sent for the hours is ignored, not trusted.
            if ($id !== '' && isset($shifts[$id])) {

                if ($name === '' || mb_strlen($name) > self::MAX_NAME) {
                    return false;
                }

                $shifts[$id]['name'] = $name;

                continue;
            }

            $clean = self::clean($name, $start, $end, $row['grace'] ?? 0);

            if ($clean === null) {
                return false;
            }

            $shifts[(string) $next] = $clean;
            $next++;
        }

        return self::store($client, $shifts, self::people($client));
    }

    /**
     * Put people on shifts. Takes pin => shift id; a blank or unknown id means
     * "leave them on the first shift", so the form can offer a blank option.
     */
    public static function savePeople(string $client, array $map): bool {

        $shifts = self::shifts($client);
        $people = [];

        foreach ($map as $pin => $id) {

            $pin = trim((string) $pin);
            $id  = trim((string) $id);

            if ($pin !== '' && isset($shifts[$id])) {
                $people[$pin] = $id;
            }
        }

        return self::store($client, $shifts, $people);
    }

    // ----------------------------------------------------------------- words

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

    /** One saved row, repaired into a full shift. Never fails. */
    private static function build(string $id, array $row): array {

        $start = self::time($row['start'] ?? null) ?? self::DEFAULT_START;
        $end   = self::time($row['end'] ?? null)   ?? self::DEFAULT_END;

        // Same time at both ends would be read as a 24 hour work day and would
        // quietly wipe out everyone's extra time. Fall back instead.
        // An end before the start just wraps: 20:00 to 05:00 is nine hours.
        if ($start === $end) {
            $start = self::DEFAULT_START;
            $end   = self::DEFAULT_END;
        }

        $length = self::minutes($end) - self::minutes($start);

        if ($length <= 0) {
            $length += 1440;
        }

        $name = trim((string) ($row['name'] ?? ''));

        return [
            'id'      => $id,
            'name'    => $name !== '' ? $name : self::DEFAULT_NAME,
            'start'   => $start,
            'end'     => $end,
            'grace'   => self::graceValue($row['grace'] ?? 0),
            'minutes' => $length,
            'text'    => self::readable($length),
        ];
    }

    /** What comes off the form, checked. Null means do not save it. */
    private static function clean(?string $name, ?string $start, ?string $end, $grace): ?array {

        $name  = trim((string) $name);
        $start = self::time($start);
        $end   = self::time($end);

        if ($name === '' || mb_strlen($name) > self::MAX_NAME) {
            return null;
        }

        if ($start === null || $end === null || $start === $end) {
            return null;
        }

        if ($grace !== null && $grace !== '' && ! is_numeric($grace)) {
            return null;
        }

        return [
            'name'  => $name,
            'start' => $start,
            'end'   => $end,
            'grace' => self::graceValue($grace),
        ];
    }

    /** Write the whole client block, dropping the old single-shift keys. */
    private static function store(string $client, array $shifts, array $people): bool {

        $rows = [];

        foreach ($shifts as $id => $shift) {
            $rows[(string) $id] = [
                'name'  => $shift['name'],
                'start' => $shift['start'],
                'end'   => $shift['end'],
                'grace' => $shift['grace'],
            ];
        }

        $all = self::all();

        $all[$client] = [
            'shifts' => $rows,
            'people' => $people,
        ];

        $written = file_put_contents(
            self::path(),
            json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        ) !== false;

        // Never hand back what we just replaced.
        self::$cache = null;
        self::$stamp = null;

        return $written;
    }

    /**
     * The whole file.
     *
     * Held in memory for the rest of the request. A report asks for a shift
     * once per person, so without this one calendar page opens and parses this
     * file hundreds of times. The stat call below is what keeps it honest —
     * if anything changes the file underneath us, it is read again.
     */
    private static function all(): array {

        $path = self::path();

        if (! is_file($path)) {
            self::$cache = null;
            self::$stamp = null;

            return [];
        }

        $stamp = filemtime($path) . ':' . filesize($path);

        if (self::$cache !== null && self::$stamp === $stamp) {
            return self::$cache;
        }

        self::$stamp = $stamp;
        self::$cache = (array) json_decode((string) file_get_contents($path), true);

        return self::$cache;
    }

    private static ?array $cache = null;

    private static ?string $stamp = null;

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

    private static function graceValue($value): int {

        return max(0, min(self::MAX_GRACE, (int) $value));
    }

}
