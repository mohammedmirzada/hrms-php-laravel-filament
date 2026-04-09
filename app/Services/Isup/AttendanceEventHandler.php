<?php

namespace App\Services\Isup;

use App\Enums\AttendanceEventSource;
use App\Enums\AttendanceEventType;
use App\Models\AttendanceDevice;
use App\Models\AttendanceEvent;
use App\Models\Employer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class AttendanceEventHandler
{
    /**
     * Parse an ISUP event XML payload and persist it as an AttendanceEvent.
     *
     * @param string   $xml          Raw XML from the device
     * @param int|null $deviceRowId  PK of the AttendanceDevice record (null if unregistered)
     */
    public function handle(string $xml, ?int $deviceRowId): void
    {
        $xml = $this->stripNamespaces($xml);

        try {
            $root = new SimpleXMLElement($xml);
        } catch (\Throwable $e) {
            Log::error('[ISUP] XML parse failed: ' . $e->getMessage() . ' | payload: ' . substr($xml, 0, 300));
            return;
        }

        $eventType = (string) ($root->eventType ?? '');

        if ($eventType !== 'AccessControllerEvent') {
            Log::debug("[ISUP] Skipping non-attendance event type: {$eventType}");
            return;
        }

        $ac             = $root->AccessControllerEvent;
        $employeeCode   = trim((string) ($ac->employeeNoString ?? ''));
        $attendStatus   = strtolower(trim((string) ($ac->attendanceStatus ?? '')));
        $dateTimeStr    = trim((string) ($root->dateTime ?? ''));

        if ($employeeCode === '') {
            Log::warning('[ISUP] Event has no employeeNoString, skipping.');
            return;
        }

        $eventAt    = $this->parseDateTime($dateTimeStr);
        $type       = $this->mapStatus($attendStatus);
        $device     = $deviceRowId ? AttendanceDevice::find($deviceRowId) : null;

        // Try to find the employer by biometric_code column (add later via migration)
        // Falls back gracefully — event is stored with is_valid=false so nothing is lost
        $employer = Employer::where('biometric_code', $employeeCode)->first();

        AttendanceEvent::create([
            'branch_id'        => $device?->branch_id,
            'employer_id'      => $employer?->id,
            'device_id'        => $deviceRowId,
            'device_user_code' => $employeeCode,
            'source'           => AttendanceEventSource::Biometric->value,
            'event_type'       => $type->value,
            'event_at'         => $eventAt,
            'raw_payload'      => ['xml' => $xml],
            'is_valid'         => $employer !== null,
            'invalid_reason'   => $employer === null
                ? "No employer matched biometric_code={$employeeCode}"
                : null,
        ]);

        $status = $employer ? "employer_id={$employer->id}" : 'UNMATCHED';
        Log::info("[ISUP] Event saved | employee={$employeeCode} type={$type->value} at={$eventAt} {$status}");
    }

    // -------------------------------------------------------------------------

    private function mapStatus(string $status): AttendanceEventType
    {
        return match ($status) {
            'checkin', 'breakin', 'normalin', 'overtimenormalin' => AttendanceEventType::In,
            'checkout', 'breakout', 'normalout'                  => AttendanceEventType::Out,
            default => AttendanceEventType::In,
        };
    }

    private function parseDateTime(string $value): string
    {
        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return now()->toDateTimeString();
        }
    }

    /** Strip XML namespaces so SimpleXMLElement works without NS prefixes */
    private function stripNamespaces(string $xml): string
    {
        return preg_replace('/\s+xmlns[^=]*="[^"]*"/', '', $xml) ?? $xml;
    }
}
