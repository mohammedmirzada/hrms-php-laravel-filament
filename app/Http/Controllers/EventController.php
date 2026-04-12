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

        // Ignore heartbeats and non-attendance events
        if (!$data || $data['eventType'] !== 'AccessControllerEvent') {
            return response('Heartbeats Ignored!', 503);
        }

        // Validate required fields and attendance status
        if (!isset($data["AccessControllerEvent"]) || $data['eventType'] !== 'AccessControllerEvent') {
            return response('Not Data Found', 404);
        }

        // Extract attendance status with a default value
        $attendanceStatus = $data["AccessControllerEvent"]['attendanceStatus'] ?? null;

        // Validate attendance status (checkIn or checkOut)
        if (!in_array($attendanceStatus, ['checkIn', 'checkOut'])) {
            return response('Not Data Found', 404);
        }

        // Extract relevant information with default values
        $macAddress = $data['macAddress'] ?? 'unknown';
        $ipAddress = $data['ipAddress'] ?? 'unknown';
        $portNo = $data['portNo'] ?? 'unknown';
        $dateTime = $data['dateTime'] ?? 'unknown';
        $employerName = $data["AccessControllerEvent"]['name'] ?? 'unknown';
        $employerId = (int) ($data["AccessControllerEvent"]['employeeNoString'] ?? 0);

        // Get the attendance device based on IP, port, and MAC address
        $getAttendanceDevice = AttendanceDevice::where('ip_address', $ipAddress)
            ->where('port', $portNo)
            ->where('mac_address', $macAddress)
            ->first();

        // Check if the device is registered in the database
        if (!$getAttendanceDevice) {
            return response('Device Not Registered', 404);
        }

        // Check if the employer exists in the database
        if (Employer::find($employerId) === null) {
            return response('Employer Not Found', 404);
        }

        Log::info('Hikvision Event Received', [
            'employer_id' => $employerId,
            'employer_name' => $employerName,
            'attendance_status' => $attendanceStatus,
            'date_time' => $dateTime
        ]);

        return response('Data Saved to Log File.', 200);
    }

}
