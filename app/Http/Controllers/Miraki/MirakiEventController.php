<?php

namespace App\Http\Controllers\Miraki;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Miraki — separate company, separate fingerprint device.
 *
 * For now this only listens and writes every push event to the Laravel log.
 * No database, no models, nothing else.
 *
 * Endpoint: POST /api/miraki/events
 */
class MirakiEventController extends Controller {

    /** Only this device is accepted. */
    private const DEVICE_MAC = '00:17:61:13:06:92';

    public function eventData(Request $request) {

        // Device sends the JSON inside the "AccessControllerEvent" form field
        $data = json_decode($request->input('AccessControllerEvent'), true);

        // Always return 200 — device must not retry
        if (!$data) {
            return response('OK', 200);
        }

        // Signature check: ignore anything not from the Miraki device
        if (($data['macAddress'] ?? null) !== self::DEVICE_MAC) {
            return response('OK', 200);
        }

        // Log the whole push event as it came
        Log::info('Miraki event', $data);

        return response('OK', 200);
    }

}
