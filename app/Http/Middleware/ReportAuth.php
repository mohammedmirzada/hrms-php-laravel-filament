<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Simple login for the attendance reports.
 *
 * Browser asks for a username and password, checked against config/miraki.php.
 * No users table, no session, no login page.
 */
class ReportAuth {

    public function handle(Request $request, Closure $next) {

        $user = (string) config('miraki.auth.username');
        $pass = (string) config('miraki.auth.password');

        // Fail closed. Without this, a missing MIRAKI_REPORT_USER / _PASS in .env
        // would let an empty username and password through.
        if ($user === '' || $pass === '') {
            Log::error('Report login is not configured (MIRAKI_REPORT_USER / MIRAKI_REPORT_PASS missing in .env)');

            return response('Report login is not configured.', 503);
        }

        $okUser = hash_equals($user, (string) $request->getUser());
        $okPass = hash_equals($pass, (string) $request->getPassword());

        if ($okUser && $okPass) {
            return $next($request);
        }

        return response('Login required.', 401, [
            'WWW-Authenticate' => 'Basic realm="Attendance report"',
        ]);
    }

}
