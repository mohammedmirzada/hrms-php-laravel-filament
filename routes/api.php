<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public API Routes (no authentication required)
|--------------------------------------------------------------------------
*/

Route::prefix('hikvision')->group(function () {

    Route::post('/events', function (Request $request) {
        $data = json_decode($request->input('AccessControllerEvent'), true);

        // Ignore heartbeats and non-attendance events
        if (!$data || $data['eventType'] !== 'AccessControllerEvent') {
            return response('OK', 200);
        }

        if (!isset($data["AccessControllerEvent"])) {
            return response('Not Data Found', 200);
        }

        $macAddress = $data['macAddress'] ?? 'unknown';
        $ipAddress = $data['ipAddress'] ?? 'unknown';
        $portNo = $data['portNo'] ?? 'unknown';
        $dateTime = $data['dateTime'] ?? 'unknown';
        $deviceID = $data['deviceID'] ?? 'unknown';
        $emplyeeName = $data["AccessControllerEvent"]['name'] ?? 'unknown';
        $employeeId = (int) $data["AccessControllerEvent"]['employeeNo'] ?? 0;
        $attendanceStatus = $data["AccessControllerEvent"]['attendanceStatus'] ?? 'unknown';

        Log::info('Hikvision Event Received', [
            'macAddress' => $macAddress,
            'ipAddress' => $ipAddress,
            'portNo' => $portNo,
            'dateTime' => $dateTime,
            'deviceID' => $deviceID,
            'employeeName' => $emplyeeName,
            'employeeId' => $employeeId,
            'attendanceStatus' => $attendanceStatus
        ]);

        return response('Data Saved to Log File.', 200);
    });

});
