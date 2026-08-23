<?php

namespace App\Http\Controllers\Meraki;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Login page for the attendance pages.
 *
 * One username and password from .env, no users table. The answer is kept in
 * the session, so the browser asks once and not on every page.
 */
class MerakiAuthController extends Controller {

    /** Session key, one per client. */
    public static function sessionKey(string $client): string {

        return "meraki_auth:{$client}";
    }

    public function showLogin(Request $request, string $client) {

        $config = config("meraki.clients.{$client}");

        abort_if(! is_array($config), 404, "Unknown client [{$client}].");

        if ($request->session()->get(self::sessionKey($client))) {
            return redirect()->route('client.report', ['client' => $client]);
        }

        return view('meraki.login', [
            'client'     => $client,
            'clientName' => $config['name'] ?? $client,
            'error'      => null,
        ]);
    }

    public function login(Request $request, string $client) {

        $config = config("meraki.clients.{$client}");

        abort_if(! is_array($config), 404, "Unknown client [{$client}].");

        $user = (string) config('meraki.auth.username');
        $pass = (string) config('meraki.auth.password');

        // Fail closed. Without this, a missing MERAKI_REPORT_USER / _PASS
        // in .env would let an empty username and password through.
        if ($user === '' || $pass === '') {
            Log::error('Report login is not configured (MERAKI_REPORT_USER / MERAKI_REPORT_PASS missing in .env)');

            return response('Report login is not configured.', 503);
        }

        $okUser = hash_equals($user, (string) $request->input('username'));
        $okPass = hash_equals($pass, (string) $request->input('password'));

        if (! $okUser || ! $okPass) {
            return view('meraki.login', [
                'client'     => $client,
                'clientName' => $config['name'] ?? $client,
                'error'      => 'Wrong username or password.',
            ]);
        }

        // New session id on login, so a stolen one from before is useless.
        $request->session()->regenerate();
        $request->session()->put(self::sessionKey($client), true);

        return redirect()->route('client.report', ['client' => $client]);
    }

    public function logout(Request $request, string $client) {

        $request->session()->forget(self::sessionKey($client));
        $request->session()->regenerate();

        return redirect()->route('client.login', ['client' => $client]);
    }

}
