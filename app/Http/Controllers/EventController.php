<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDevice;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventController extends Controller {

    /**
     * Example Json Data from Hikvision Device
        {
            "data": {
                "ipAddress": "192.168.1.200",
                "portNo": 443,
                "protocol": "HTTPS",
                "macAddress": "a4:d5:c2:62:3e:31",
                "channelID": 1,
                "dateTime": "2026-04-12T16:09:53+03:00",
                "activePostCount": 1,
                "eventType": "AccessControllerEvent",
                "eventState": "active",
                "eventDescription": "Access Controller Event",
                "deviceID": "DeviceLF1",
                "shortSerialNumber": "GA6820584",
                "AccessControllerEvent": {
                    "deviceName": "Access Controller",
                    "majorEventType": 5,
                    "subEventType": 22,
                    "doorNo": 1,
                    "serialNo": 272,
                    "frontSerialNo": 271,
                    "label": "",
                    "purePwdVerifyEnable": true
                }
            }
        }
            OR
        {
            "data": {
                "ipAddress": "192.168.1.200",
                "portNo": 443,
                "protocol": "HTTPS",
                "macAddress": "a4:d5:c2:62:3e:31",
                "channelID": 1,
                "dateTime": "2026-04-12T16:18:20+03:00",
                "activePostCount": 1,
                "eventType": "AccessControllerEvent",
                "eventState": "active",
                "eventDescription": "Access Controller Event",
                "deviceID": "DeviceLF1",
                "shortSerialNumber": "GA6820584",
                "AccessControllerEvent": {
                    "deviceName": "Access Controller",
                    "majorEventType": 5,
                    "subEventType": 38,
                    "name": "Mohammed Dlshad Qasim",
                    "cardReaderNo": 1,
                    "employeeNoString": "1",
                    "serialNo": 273,
                    "userType": "normal",
                    "currentVerifyMode": "faceOrFpOrCardOrPw",
                    "frontSerialNo": 272,
                    "attendanceStatus": "checkIn",
                    "label": "Check In",
                    "purePwdVerifyEnable": true
                }
            }
        }
    **/

    public function eventData(Request $request) {

        // Decode the incoming JSON data
        $data = json_decode($request->input('AccessControllerEvent'), true);

        // Always return 200 immediately — device must not retry
        if (!$data) {
            return response('OK', 200);
        }

        // Silently ignore heartbeats and non-attendance events
        if (($data['eventType'] ?? '') !== 'AccessControllerEvent') {
            return response('OK', 200);
        }

        $ac               = $data['AccessControllerEvent'] ?? null;
        $attendanceStatus = $ac['attendanceStatus'] ?? null;

        // Only process actual check-in / check-out punches, ignore door/system events
        if (!$ac || !in_array($attendanceStatus, ['checkIn', 'checkOut'])) {
            return response('OK', 200);
        }


        $macAddress   = $data['macAddress']         ?? null;
        $dateTime     = $data['dateTime']           ?? now()->toIso8601String();
        $employerName = $ac['name']                ?? 'unknown';
        $employeeCode = $ac['employeeNoString']    ?? null;

        // Look up device by MAC address
        $device = AttendanceDevice::where('mac_address', $macAddress)->first();

        if (!$device) {
            Log::warning('Hikvision: device not registered', ['mac_address' => $macAddress]);
            return response('OK', 200);
        }

        // Look up employer by id (employeeNoString from device matches employer id for now)
        $employer = $employeeCode
            ? Employer::find((int) $employeeCode)
            : null;

        if (!$employer) {
            Log::warning('Hikvision: employer not found', ['employee_code' => $employeeCode, 'name' => $employerName]);
            return response('OK', 200);
        }

        Log::info('Hikvision Punch', [
            'employer_id'       => $employer->id,
            'employer_name'     => $employerName,
            'attendance_status' => $attendanceStatus,
            'date_time'         => $dateTime,
        ]);

        return response('OK', 200);
    }

}
