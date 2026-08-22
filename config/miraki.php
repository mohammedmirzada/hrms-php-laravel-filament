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
    | 'alternate'  the device does not say, so punches of one person on one day
    |              are read in order: 1st IN, 2nd OUT, 3rd IN, 4th OUT...
    |
    | 'device'     trust the device. Only correct once the punch state key is
    |              switched on at the device (Menu -> Attendance -> punch state),
    |              so staff press In or Out before the finger. Until then every
    |              punch arrives as 0 and would all show as IN.
    |
    | Switch this to 'device' the day you enable the state key — that is also
    | the only way two INs in a row can be recorded as two real INs.
    |
    */
    'punch_state' => 'alternate',

];
