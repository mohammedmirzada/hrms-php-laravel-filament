<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDevice;
use App\Models\Employer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventController extends Controller {

    public function eventData(Request $request) {

        // Decode the incoming JSON data
        $data = json_decode($request->input('AccessControllerEvent'), true);

        // Ignore heartbeats and non-attendance events
        if (!$data || $data['eventType'] !== 'AccessControllerEvent') {
            Log::error('Received non-attendance event or invalid data', ['data' => $data]);
            return response('Heartbeats Ignored!', 503);
        }

        // Validate required fields and attendance status
        if (!isset($data["AccessControllerEvent"]) || $data['eventType'] !== 'AccessControllerEvent') {
            Log::error('Invalid event data received', ['data' => $data]);
            return response('Not Data Found', 404);
        }

        // Extract attendance status with a default value
        $attendanceStatus = $data["AccessControllerEvent"]['attendanceStatus'] ?? null;

        // Validate attendance status (checkIn or checkOut)
        if (!in_array($attendanceStatus, ['checkIn', 'checkOut'])) {
            Log::error('Invalid attendance status received', ['attendanceStatus' => $attendanceStatus]);
            return response('Not Data Found', 404);
        }

        // Extract relevant information with default values
        $macAddress = $data['macAddress'] ?? 'unknown';
        $ipAddress = $data['ipAddress'] ?? 'unknown';
        $portNo = $data['portNo'] ?? 'unknown';
        $dateTime = $data['dateTime'] ?? 'unknown';
        $employerName = $data["AccessControllerEvent"]['name'] ?? 'unknown';
        $employerId = (int) ($data["AccessControllerEvent"]['employeeNo'] ?? 0);

        // Get the attendance device based on IP, port, and MAC address
        $getAttendanceDevice = AttendanceDevice::where('ip_address', $ipAddress)
            ->where('port', $portNo)
            ->where('mac_address', $macAddress)
            ->first();

        // Check if the device is registered in the database
        if (!$getAttendanceDevice) {
            Log::error('Device Not Registered', [
                'ip_address' => $ipAddress,
                'port' => $portNo,
                'mac_address' => $macAddress
            ]);
            return response('Device Not Registered', 404);
        }

        // Check if the employer exists in the database
        if (Employer::find($employerId) === null) {
            Log::error('Employer Not Found', ['employer_id' => $employerId]);
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
