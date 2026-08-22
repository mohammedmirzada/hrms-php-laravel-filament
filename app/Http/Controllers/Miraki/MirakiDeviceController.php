<?php

namespace App\Http\Controllers\Miraki;

use App\Http\Controllers\Controller;

use App\Models\Miraki;
use App\Models\MirakiUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco ADMS / PUSH listener.
 *
 * Devices only talk to fixed /iclock/* paths, they cannot use /api/*, and the
 * path cannot be changed on the device. So every client's device hits the same
 * three endpoints and is identified by serial number — see config/miraki.php.
 *
 * Every reply must be text/plain, or the device retries forever.
 *
 * Punches (ATTLOG) carry only the PIN, never the name.
 * Names come from the USERINFO table, which the device sends when we ask.
 */
class MirakiDeviceController extends Controller {

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

        // Device wants the number of rows we accepted
        return $this->plain('OK: ' . count($this->lines($body)));
    }

    /** Device polls this every 30s asking for commands. */
    public function getrequest(Request $request) {

        $sn = (string) $request->query('SN');

        // pull() reads and clears, so the command goes out only once.
        if ($this->isKnownDevice($sn) && Cache::store('file')->pull($this->pullKey($sn))) {

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

    /**
     * Refresh the pin -> name list without rebooting the device.
     * Picked up on the device's next poll (max 30s).
     */
    public function pullUsers(string $client) {

        $sn = $this->deviceSn($client);

        abort_if($sn === '', 404, "Unknown client [{$client}]. If you just deployed, run: php artisan config:clear");

        Cache::store('file')->put($this->pullKey($sn), true, now()->addMinutes(10));

        // No session on these routes, so no flash message — plain reply instead.
        if (! Cache::store('file')->has($this->pullKey($sn))) {
            return response('FAILED: file cache is not writable (storage/framework/cache).', 500);
        }

        return response()->view('miraki.queued', [
            'client' => $client,
            'sn'     => $sn,
        ]);
    }

    // ------------------------------------------------------------- handshake

    /**
     * Config block the device asks for at boot. Without Stamp/OpStamp it keeps
     * re-registering, and without Delay it polls every second.
     *
     * No TimeZone line on purpose — sending it overwrites the device clock.
     */
    private function handshake(string $sn) {

        // Device just booted, so refresh the pin -> name list too
        Cache::store('file')->put($this->pullKey($sn), true, now()->addMinutes(10));

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
            . "Encrypt=0\r\n"
        );
    }

    // ---------------------------------------------------------------- parsing

    /**
     * ATTLOG, one punch per line, tab separated:
     *   PIN <t> YYYY-MM-DD HH:MM:SS <t> status <t> verify <t> workcode <t> ...
     */
    private function storePunches(string $body, string $sn): void {

        foreach ($this->lines($body) as $line) {

            $f = explode("\t", $line);

            if (count($f) < 2 || trim($f[0]) === '' || trim($f[1]) === '') {
                Log::warning('Bad punch line', ['SN' => $sn, 'line' => $line]);
                continue;
            }

            // firstOrCreate + the unique index means a re-sent punch is ignored
            Miraki::firstOrCreate(
                [
                    'device_sn'  => $sn,
                    'pin'        => trim($f[0]),
                    'punched_at' => trim($f[1]),
                ],
                [
                    'status' => (int) ($f[2] ?? 0),
                    'verify' => isset($f[3]) ? (int) $f[3] : null,
                    'raw'    => $line,
                ]
            );
        }
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

            MirakiUser::updateOrCreate(
                [
                    'device_sn' => $sn,
                    'pin'       => $fields['PIN'],
                ],
                [
                    'name'      => $fields['Name'] ?? ('PIN ' . $fields['PIN']),
                    'privilege' => (int) ($fields['Pri'] ?? 0),
                ]
            );
        }
    }

    // ----------------------------------------------------------------- config

    /**
     * Serial numbers listed in config/miraki.php, which reads them from .env.
     * Blanks are filtered out so a missing env var can never match.
     */
    private function isKnownDevice(string $sn): bool {

        if ($sn === '') {
            return false;
        }

        $known = array_filter(array_column((array) config('miraki.clients'), 'device_sn'));

        return in_array($sn, $known, true);
    }

    /** Slug -> serial number. */
    private function deviceSn(string $client): string {

        return (string) config("miraki.clients.{$client}.device_sn");
    }

    private function pullKey(string $sn): string {

        return "zk:pull_users:{$sn}";
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
