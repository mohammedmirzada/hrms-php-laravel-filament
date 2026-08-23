<?php

namespace App\Http\Controllers\Meraki;

use App\Http\Controllers\Controller;
use App\Models\MerakiUser;
use App\Support\MerakiSettings;
use Illuminate\Http\Request;

/**
 * Settings page. Shifts, and who is on which one.
 *
 * Saved to storage, so it can all be changed any time without a deploy.
 * Shifts cannot be deleted — old reports are measured against the shift a
 * person was on, so an id has to keep meaning the same thing forever.
 */
class MerakiSettingsController extends Controller {

    public function edit(Request $request, string $client) {

        return view('meraki.settings', $this->page($client) + [
            'saved' => (bool) $request->query('saved'),
            'error' => null,
        ]);
    }

    /** Save every shift row at once — changes and new ones together. */
    public function saveShifts(Request $request, string $client) {

        $this->page($client);   // 404s on an unknown client

        $ok = MerakiSettings::saveShifts($client, (array) $request->input('shifts', []));

        return $this->done($client, $ok, 'Could not save the shifts.');
    }

    /** Put employees on shifts. */
    public function savePeople(Request $request, string $client) {

        $this->page($client);   // 404s on an unknown client

        $ok = MerakiSettings::savePeople($client, (array) $request->input('people', []));

        return $this->done($client, $ok, 'Could not save who is on which shift.');
    }

    /** Back to the page, either with a tick or with the reason it failed. */
    private function done(string $client, bool $ok, string $what) {

        $page = $this->page($client);   // 404s on an unknown client

        if ($ok) {
            return redirect()->route('client.settings', ['client' => $client, 'saved' => 1]);
        }

        return view('meraki.settings', $page + [
            'saved' => false,
            'error' => $what . ' Every row needs a name, both times must look like'
                . ' 08:00, and a shift cannot start and end at the same time. If it all'
                . ' looks right, storage/app is not writable.',
        ]);
    }

    /** Shared page data, and the unknown-client check. */
    private function page(string $client): array {

        $config = config("meraki.clients.{$client}");

        abort_if(! is_array($config), 404, "Unknown client [{$client}].");

        $shifts = MerakiSettings::shifts($client);

        return [
            'client'     => $client,
            'clientName' => $config['name'] ?? $client,
            'shifts'     => $shifts,
            'defaultId'  => (string) array_key_first($shifts),
            'people'     => MerakiUser::where('device_sn', $config['device_sn'] ?? '')
                ->orderBy('name')
                ->pluck('name', 'pin'),
            'assigned'   => MerakiSettings::people($client),
        ];
    }

}
