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

];
