<?php

namespace App\Http\Controllers\Meraki;

use App\Http\Controllers\Controller;
use App\Support\MerakiSettings;
use Illuminate\Http\Request;

/**
 * Settings page. One shift start and end time, used for everybody.
 * Saved to storage, so it can be changed any time without a deploy.
 */
class MerakiSettingsController extends Controller {

    public function edit(Request $request, string $client) {

        return view('meraki.settings', $this->page($client) + [
            'saved' => (bool) $request->query('saved'),
            'error' => null,
        ]);
    }

    public function update(Request $request, string $client) {

        $page = $this->page($client);   // 404s on an unknown client

        $ok = MerakiSettings::saveShift(
            $client,
            $request->input('shift_start'),
            $request->input('shift_end')
        );

        if (! $ok) {
            return view('meraki.settings', $page + [
                'saved' => false,
                'error' => 'Could not save. Both times must look like 08:00, and the start and'
                    . ' end cannot be the same time. If they look right, storage/app is not writable.',
            ]);
        }

        return redirect()->route('client.settings', ['client' => $client, 'saved' => 1]);
    }

    /** Shared page data, and the unknown-client check. */
    private function page(string $client): array {

        $config = config("meraki.clients.{$client}");

        abort_if(! is_array($config), 404, "Unknown client [{$client}].");

        $shift = MerakiSettings::shift($client);

        return [
            'client'     => $client,
            'clientName' => $config['name'] ?? $client,
            'shift'      => $shift,
            'shiftText'  => MerakiSettings::readable(MerakiSettings::shiftMinutes($client)),
            'isNight'    => MerakiSettings::minutes($shift['end']) <= MerakiSettings::minutes($shift['start']),
        ];
    }

}
