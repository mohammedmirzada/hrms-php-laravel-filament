<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Meraki\MerakiAuthController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Guards the attendance pages.
 *
 * Logging in happens on a normal login page — see MerakiAuthController.
 * Here we only check the session and send visitors to that page.
 */
class ReportAuth {

    public function handle(Request $request, Closure $next) {

        $client = (string) $request->route('client');

        // Fail closed if the login is not set up at all.
        if ((string) config('meraki.auth.username') === ''
            || (string) config('meraki.auth.password') === '') {

            Log::error('Report login is not configured (MERAKI_REPORT_USER / MERAKI_REPORT_PASS missing in .env)');

            return response('Report login is not configured.', 503);
        }

        if ($request->session()->get(MerakiAuthController::sessionKey($client))) {
            return $next($request);
        }

        // Remember the page they wanted, so logging in does not dump them on
        // the calendar when they clicked a link to something else.
        if ($request->isMethod('get')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->route('client.login', ['client' => $client]);
    }

}
