<?php

/*
|--------------------------------------------------------------------------
| Attendance configuration (hardcoded company rules)
|--------------------------------------------------------------------------
|
| These are the fixed Lionsfort rules that used to live in the dynamic
| Shift / AttendanceDevice tables. Edit the values here — no admin UI.
|
*/

return [

    /*
    | Company working days, ISO-8601 day numbers (1 = Monday ... 7 = Sunday).
    | Used by the attendance reports to know which days count as working days
    | (an employee is only "absent" on a working day that isn't a holiday).
    |
    | NOTE: default below is Sun–Thu — adjust to Lionsfort's actual week.
    */
    'working_days' => [7, 1, 2, 3, 4], // Sun, Mon, Tue, Wed, Thu

    /*
    | The single fingerprint device. Punches are received at
    | POST /api/hikvision/events and matched by MAC address.
    */
    'device' => [
        'vendor'      => 'Hikvision',
        'mac_address' => 'a4:d5:c2:62:3e:31',
        'ip_address'  => '192.168.1.200',
        'port'        => 443,
    ],

];
