<?php

/*
|--------------------------------------------------------------------------
| Fingerprint device clients
|--------------------------------------------------------------------------
|
| Every ZKTeco device posts to the same fixed /iclock/* paths — the path is
| burned into the firmware and cannot be changed. So clients are told apart
| by device serial number, not by URL.
|
| The serial number IS the device's only password — anyone who knows it can
| post fake punches. So serials and the report login live in .env, never here.
|
| TO ADD A SECOND CLIENT:
|   1. add one block under 'clients' below
|   2. put its serial in .env  (e.g. ACME_DEVICE_SN=...)
|   3. point their device at this server (Cloud Server Setting -> ADMS, port 80)
|
| Their report is then live at  /<slug>/report  — no code change needed.
|
*/

return [

    'clients' => [

        'meraki' => [
            'name'      => 'Meraki',
            // The old MIRAKI_ spelling still works, so a server whose .env
            // was not updated yet keeps running. Rename it when you can.
            'device_sn' => env('MERAKI_DEVICE_SN', env('MIRAKI_DEVICE_SN')),   // ZKTeco iFace950 Plus
        ],

        // 'acme' => [
        //     'name'      => 'Acme Trading',
        //     'device_sn' => env('ACME_DEVICE_SN'),
        // ],

    ],

    /*
    | Login for the report pages, same login for every client.
    | Set MERAKI_REPORT_USER and MERAKI_REPORT_PASS in .env.
    */
    'auth' => [
        'username' => env('MERAKI_REPORT_USER', env('MIRAKI_REPORT_USER')),
        'password' => env('MERAKI_REPORT_PASS', env('MIRAKI_REPORT_PASS')),
    ],

    /*
    |--------------------------------------------------------------------------
    | How the report decides IN or OUT
    |--------------------------------------------------------------------------
    |
    | 'auto'       recommended. Uses the device state as soon as the device
    |              actually starts sending one, otherwise counts. Nothing to
    |              change by hand on the day the state key is enabled.
    |
    | 'alternate'  always count: 1st punch of the day IN, 2nd OUT, 3rd IN...
    |              Cannot show two INs in a row — the second becomes OUT.
    |
    | 'device'     always trust the device. Wrong until the punch state key is
    |              switched on, because every punch arrives as 0 = IN.
    |
    | IMPORTANT: a real IN / OUT — where someone can punch IN ten times in a
    | row — is only possible with the punch state key enabled on the device:
    |   Menu -> System -> Attendance -> Punch State Options -> Manual
    |   Menu -> Personalize -> Shortcut Key Mappings -> Check In / Check Out
    | Staff then press In or Out before the finger.
    |
    */
    'punch_state' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Hours and overtime
    |--------------------------------------------------------------------------
    |
    | Worked hours = every IN -> OUT pair added up. A break in the middle is
    | NOT paid. An extra IN while already in is ignored, an OUT with no IN
    | before it is ignored, so a wrong punch never makes the hours negative.
    |
    | Overtime = worked hours - shift length, when that is more than zero.
    | Shift 08:00-17:00 is 9h, so 12h worked = 3h overtime, no matter whether
    | the person came early or left late.
    |
    | The device's own "overtime in / overtime out" keys are NOT used. Only
    | check in and check out count.
    |
    */

    /*
    | Calendar starts each week on this day. 6 = Saturday (Iraq), 1 = Monday.
    */
    'week_starts_on' => 6,

];
