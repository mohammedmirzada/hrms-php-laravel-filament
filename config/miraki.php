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

        'miraki' => [
            'name'      => 'Miraki',
            'device_sn' => env('MIRAKI_DEVICE_SN'),   // ZKTeco iFace950 Plus
        ],

        // 'acme' => [
        //     'name'      => 'Acme Trading',
        //     'device_sn' => env('ACME_DEVICE_SN'),
        // ],

    ],

    /*
    | Login for the report pages, same login for every client.
    | Set MIRAKI_REPORT_USER and MIRAKI_REPORT_PASS in .env.
    */
    'auth' => [
        'username' => env('MIRAKI_REPORT_USER'),
        'password' => env('MIRAKI_REPORT_PASS'),
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

];
