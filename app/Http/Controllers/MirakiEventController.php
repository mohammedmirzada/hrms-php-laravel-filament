<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Miraki — separate company, separate fingerprint device.
 *
 * Same Hikvision JSON payload as EventController, but nothing here touches
 * the HRMS models or database. Punches will be pushed to a Google Spreadsheet.
 *
 * Endpoint: POST /api/miraki/events
 */
class MirakiEventController extends Controller {

    /**
     * Hardcoded device identity — fill these in later.
     * Used only to confirm the request really came from the Miraki device.
     */
    private const DEVICE_MAC    = 'xx:xx:xx:xx:xx:xx';
    private const DEVICE_IP     = '192.168.x.x';
    private const DEVICE_ID     = 'DeviceMiraki1';
    private const DEVICE_SERIAL = 'XXXXXXXXX';

    /** Google Sheet target — fill in later. */
    private const SHEET_ID    = '';
    private const SHEET_RANGE = 'Attendance!A:F';

    public function eventData(Request $request) {

        // Device sends the JSON inside the "AccessControllerEvent" form field
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

        // Only real check-in / check-out punches, ignore door/system events
        if (!$ac || !in_array($attendanceStatus, ['checkIn', 'checkOut'])) {
            return response('OK', 200);
        }

        // Confirm this is the Miraki device and not some other device
        // TODO: enable once the real MAC is hardcoded above
        // if (($data['macAddress'] ?? null) !== self::DEVICE_MAC) {
        //     Log::warning('Miraki: unknown device', ['mac_address' => $data['macAddress'] ?? null]);
        //     return response('OK', 200);
        // }

        // Fields we will write to the sheet
        $row = [
            'employee_code' => $ac['employeeNoString'] ?? null,
            'name'          => $ac['name']             ?? 'unknown',
            'event_type'    => $attendanceStatus === 'checkIn' ? 'IN' : 'OUT',
            'event_at'      => $data['dateTime']       ?? now()->toIso8601String(),
            'serial_no'     => $ac['serialNo']         ?? null,
            'device_id'     => $data['deviceID']       ?? null,
        ];

        // TODO: append $row to the Google Spreadsheet
        //   - service account credentials (env: MIRAKI_GOOGLE_CREDENTIALS)
        //   - append to self::SHEET_ID / self::SHEET_RANGE
        //   - wrap in try/catch, never let a sheet failure break the 200 response
        //   - duplicate guard: skip if serial_no already written (device retries)

        Log::info('Miraki punch', $row);

        return response('OK', 200);
    }

}
