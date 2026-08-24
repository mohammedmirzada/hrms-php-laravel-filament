<?php

namespace App\Http\Controllers\Meraki;

use App\Http\Controllers\Controller;

use App\Models\Meraki;
use App\Models\MerakiUser;
use App\Support\MerakiSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco ADMS / PUSH listener.
 *
 * Devices only talk to fixed /iclock/* paths, they cannot use /api/*, and the
 * path cannot be changed on the device. So every client's device hits the same
 * three endpoints and is identified by serial number — see config/meraki.php.
 *
 * Every reply must be text/plain, or the device retries forever.
 *
 * Punches (ATTLOG) carry only the PIN, never the name.
 * Names come from the USERINFO table, which the device sends when we ask.
 */
class MerakiDeviceController extends Controller {

    /**
     * GET  — handshake, device asks for its config.
     * POST — device uploads its options, punches or users.
     */
    public function cdata(Request $request) {

        $sn    = (string) $request->query('SN');
        $table = $request->query('table');

        // Unknown device: answer OK so it stops retrying, but store nothing.
        if (! $this->isKnownDevice($sn)) {
            Log::warning('Unknown fingerprint device', ['SN' => $sn]);

            return $this->plain('OK');
        }

        if ($request->isMethod('get')) {
            return $this->handshake($sn);
        }

        // Device uploading its OWN options. Answering with the config block
        // here made it loop every second, so just acknowledge.
        if ($table === 'options') {
            return $this->plain('OK');
        }

        $body = $request->getContent();

        if ($table === 'ATTLOG') {
            $this->storePunches($body, $sn);
        }

        // USERINFO answers our DATA QUERY. OPERLOG carries the same
        // "USER PIN=" lines when someone is enrolled on the device.
        if (str_contains($body, 'USER PIN=')) {
            $this->storeUsers($body, $sn);
        }

        // Anything else the device sends, written down once so it stops being
        // a guess. Names went missing in August 2026 and there was no way to
        // tell "the device never sent them" from "it sent them in a shape we
        // do not read". This makes the difference visible in the log.
        if ($table !== 'ATTLOG' && ! str_contains($body, 'USER PIN=')) {
            Log::info('Device sent something we do not handle', [
                'SN'    => $sn,
                'table' => $table,
                'body'  => mb_substr(trim($body), 0, 500),
            ]);
        }

        // Device wants the number of rows we accepted
        return $this->plain('OK: ' . count($this->lines($body)));
    }

    /**
     * Device polls this every 30s asking for commands.
     *
     * Names only ever arrive because we ask. This device does not announce a
     * new person as they are enrolled — proved on 2026-08-24, when a man
     * punched at 11:07 with no name and his name landed at 12:13:10, the
     * second we asked for the list. So we ask three ways:
     *   - once a day, to catch a name changed on the device
     *   - on every reboot, from the handshake
     *   - the moment a PIN we have no name for punches
     */
    public function getrequest(Request $request) {

        $sn = (string) $request->query('SN');

        if ($this->isKnownDevice($sn) && ! Cache::store('file')->has($this->syncKey($sn))) {

            Cache::store('file')->put($this->syncKey($sn), true, now()->addDay());

            Log::info('Asking device for its user list', ['SN' => $sn]);

            return $this->plain("C:1:DATA QUERY USERINFO\r\n");
        }

        return $this->plain('OK');
    }

    /** Device reports the result of a command. Return=0 means it worked. */
    public function devicecmd(Request $request) {

        Log::info('Device cmd result', [
            'SN'   => $request->query('SN'),
            'body' => trim($request->getContent()),
        ]);

        return $this->plain('OK');
    }

    // ------------------------------------------------------------- handshake

    /**
     * Config block the device asks for at boot. Without Stamp/OpStamp it keeps
     * re-registering, and without Delay it polls every second.
     *
     * TimeZone is sent on purpose: this device's clock was 5 hours ahead of
     * Baghdad, so the server states the offset instead of letting the device
     * keep its own. Whether the firmware acts on it is not guaranteed — if the
     * clock does not move after a reboot, set it by hand on the device.
     */
    private function handshake(string $sn) {

        // Device just booted, so ask for the name list again on its next poll
        Cache::store('file')->forget($this->syncKey($sn));

        return $this->plain(
            "GET OPTION FROM: {$sn}\r\n"
            . "Stamp=9999\r\n"
            . "OpStamp=9999\r\n"
            . "ErrorDelay=30\r\n"
            . "Delay=30\r\n"                 // seconds between getrequest polls
            . "RequestDelay=30\r\n"
            . "TransTimes=00:00;14:00\r\n"
            . "TransInterval=1\r\n"
            . "TransFlag=1111000000\r\n"
            . "TransTables=User Transaction\r\n"
            . "Realtime=1\r\n"               // push punches the moment they happen
            . "TimeZone=3\r\n"               // Baghdad, UTC+3
            . "Encrypt=0\r\n"
        );
    }

    // ---------------------------------------------------------------- parsing

    /**
     * ATTLOG, one punch per line, tab separated:
     *   PIN <t> YYYY-MM-DD HH:MM:SS <t> status <t> verify <t> workcode <t> ...
     */
    private function storePunches(string $body, string $sn): void {

        $pins = [];

        foreach ($this->lines($body) as $line) {

            $f = explode("\t", $line);

            if (count($f) < 2 || trim($f[0]) === '' || trim($f[1]) === '') {
                Log::warning('Bad punch line', ['SN' => $sn, 'line' => $line]);
                continue;
            }

            $pin = trim($f[0]);

            // firstOrCreate + the unique index means a re-sent punch is ignored
            Meraki::firstOrCreate(
                [
                    'device_sn'  => $sn,
                    'pin'        => $pin,
                    'punched_at' => trim($f[1]),
                ],
                [
                    'status'   => (int) ($f[2] ?? 0),
                    'verify'   => isset($f[3]) ? (int) $f[3] : null,
                    'raw'      => $line,

                    // Written once, here. Moving this person to another shift
                    // tomorrow must not change what today was measured against.
                    'shift_id' => $this->shiftId($sn, $pin),
                ]
            );

            $pins[$pin] = true;
        }

        $this->askForMissingNames($sn, array_keys($pins));
    }

    /**
     * Somebody punched whose name we have never been given.
     *
     * This device does NOT push a new person as they are enrolled — it only
     * ever sends names when asked, so a person added today reads as "PIN 7"
     * until the next daily ask. A punch is the proof they exist, so drop the
     * "asked recently" mark and the next poll, 30s away, asks again.
     *
     * It cannot loop: a person with no name typed on the device is stored as
     * "PIN 7", which counts as a name, so they are never asked for twice.
     */
    private function askForMissingNames(string $sn, array $pins): void {

        if ($pins === [] || ! Cache::store('file')->has($this->syncKey($sn))) {
            return;
        }

        $known = MerakiUser::where('device_sn', $sn)
            ->whereIn('pin', $pins)
            ->pluck('pin')
            ->all();

        $missing = array_diff($pins, $known);

        if ($missing === []) {
            return;
        }

        Log::info('Punch from a PIN with no name, asking for the list', [
            'SN'   => $sn,
            'pins' => array_values($missing),
        ]);

        Cache::store('file')->forget($this->syncKey($sn));
    }

    /**
     * The shift this person is on right now, as an id, for stamping onto a
     * punch. Null if the serial belongs to no client we know.
     */
    private function shiftId(string $sn, string $pin): ?string {

        foreach ((array) config('meraki.clients') as $slug => $client) {

            if (($client['device_sn'] ?? null) === $sn) {
                return MerakiSettings::shiftFor($slug, $pin)['id'];
            }
        }

        return null;
    }

    /**
     * USERINFO, one user per line, tab separated key=value:
     *   USER PIN=1 <t> Name=Mohammed Qasim <t> Pri=14 <t> Passwd=... <t> ...
     *
     * Passwd and Card are deliberately not stored.
     */
    private function storeUsers(string $body, string $sn): void {

        foreach ($this->lines($body) as $line) {

            if (! str_contains($line, 'USER PIN=')) {
                continue;
            }

            $fields = [];

            foreach (explode("\t", str_replace('USER PIN=', 'PIN=', $line)) as $pair) {
                if (str_contains($pair, '=')) {
                    [$key, $value] = explode('=', $pair, 2);
                    $fields[trim($key)] = trim($value);
                }
            }

            if (($fields['PIN'] ?? '') === '') {
                continue;
            }

            // A person can be enrolled with the name left blank. Storing that
            // as an empty string would show a nameless row on every report,
            // so fall back to the PIN, which is at least something to read.
            $name = trim((string) ($fields['Name'] ?? ''));

            MerakiUser::updateOrCreate(
                [
                    'device_sn' => $sn,
                    'pin'       => $fields['PIN'],
                ],
                [
                    'name'      => $name !== '' ? $name : ('PIN ' . $fields['PIN']),
                    'privilege' => (int) ($fields['Pri'] ?? 0),
                ]
            );
        }
    }

    // ----------------------------------------------------------------- config

    /**
     * Serial numbers listed in config/meraki.php, which reads them from .env.
     * Blanks are filtered out so a missing env var can never match.
     */
    private function isKnownDevice(string $sn): bool {

        if ($sn === '') {
            return false;
        }

        $known = array_filter(array_column((array) config('meraki.clients'), 'device_sn'));

        return in_array($sn, $known, true);
    }

    /** Set while the name list is fresh. Gone = ask the device again. */
    private function syncKey(string $sn): string {

        return "zk:users_synced:{$sn}";
    }

    // ------------------------------------------------------------------ utils

    /** Split a device body into non-empty trimmed lines. */
    private function lines(string $body): array {

        return array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', trim($body))),
            fn ($line) => $line !== ''
        ));
    }

    private function plain(string $body) {

        return response($body, 200)->header('Content-Type', 'text/plain');
    }

}
