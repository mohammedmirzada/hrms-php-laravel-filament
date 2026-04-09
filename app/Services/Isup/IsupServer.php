<?php

namespace App\Services\Isup;

use App\Models\AttendanceDevice;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

/**
 * Non-blocking TCP server for ISUP 5.0.
 *
 * Accepts ALL device connections regardless of DB match.
 * Every punch is printed to the terminal and saved to DB.
 * Unmatched devices / employees are stored with is_valid=false — nothing is lost.
 */
class IsupServer
{
    /** @var resource */
    private $server;

    /**
     * Per-connection state keyed by peer address.
     *
     * @var array<string, array{socket: resource, buffer: string, deviceRowId: int|null, deviceId: string|null, authenticated: bool}>
     */
    private array $clients = [];

    private AttendanceEventHandler $handler;

    /** Output callback → writes a line to the Artisan terminal */
    private \Closure $out;

    private bool $running = false;

    public function __construct(AttendanceEventHandler $handler, \Closure $out)
    {
        $this->handler = $handler;
        $this->out     = $out;
    }

    // -------------------------------------------------------------------------

    public function start(string $host, int $port): void
    {
        $this->server = stream_socket_server(
            "tcp://{$host}:{$port}",
            $errno, $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
        );

        if ($this->server === false) {
            throw new \RuntimeException("Cannot bind TCP {$host}:{$port} — {$errstr} (errno {$errno})");
        }

        stream_set_blocking($this->server, false);
        $this->running = true;

        $this->print("Listening on {$host}:{$port} — waiting for devices...");

        while ($this->running) {
            $read   = array_merge([$this->server], array_column($this->clients, 'socket'));
            $write  = null;
            $except = null;

            $changed = @stream_select($read, $write, $except, 1);

            if ($changed === false || $changed === 0) {
                continue;
            }

            // ── Accept new connection ─────────────────────────────────────────
            if (in_array($this->server, $read, true)) {
                $socket = @stream_socket_accept($this->server, 0, $peer);
                if ($socket !== false) {
                    stream_set_blocking($socket, false);
                    $id = $peer ?? uniqid('dev_');
                    $this->clients[$id] = [
                        'socket'        => $socket,
                        'buffer'        => '',
                        'deviceRowId'   => null,
                        'deviceId'      => null,
                        'authenticated' => false,
                    ];
                    $this->print("→ Device connected from {$id}");
                }
                unset($read[array_search($this->server, $read, true)]);
            }

            // ── Read from existing clients ────────────────────────────────────
            foreach ($this->clients as $id => &$meta) {
                if (!in_array($meta['socket'], $read, true)) {
                    continue;
                }

                $chunk = fread($meta['socket'], 65536);

                if ($chunk === false || $chunk === '') {
                    $this->disconnect($id);
                    continue;
                }

                $meta['buffer'] .= $chunk;
                $this->drainBuffer($id, $meta);
            }
            unset($meta);
        }

        foreach (array_keys($this->clients) as $id) {
            $this->disconnect($id);
        }

        fclose($this->server);
        $this->print('Server stopped.');
    }

    public function stop(): void
    {
        $this->running = false;
    }

    // -------------------------------------------------------------------------

    private function drainBuffer(string $id, array &$meta): void
    {
        while (strlen($meta['buffer']) >= IsupFrame::HEADER_SIZE) {
            $frame = IsupFrame::tryParse($meta['buffer']);
            if ($frame === null) {
                break;
            }
            $this->dispatch($id, $meta, $frame);
        }
    }

    private function dispatch(string $id, array &$meta, IsupFrame $frame): void
    {
        switch ($frame->msgType) {
            case IsupFrame::MSG_REGISTER_REQ:
                $this->onRegister($id, $meta, $frame);
                break;

            case IsupFrame::MSG_KEEPALIVE_REQ:
                $this->send($meta['socket'], IsupFrame::keepaliveAck($frame->sequence));
                // keepalives are silent — no terminal noise
                break;

            case IsupFrame::MSG_EVENT:
                $this->onEvent($id, $meta, $frame);
                break;

            default:
                $this->print('  [?] Unknown frame type 0x' . dechex($frame->msgType) . " from {$id}");
        }
    }

    // ── Handlers ──────────────────────────────────────────────────────────────

    private function onRegister(string $id, array &$meta, IsupFrame $frame): void
    {
        try {
            $xml  = $this->stripNamespaces($frame->data);
            $root = new SimpleXMLElement(empty($xml) ? '<ISUPRegister/>' : $xml);

            $deviceId = trim((string) ($root->deviceID ?? $root->DeviceID ?? ''));
            $isupKey  = trim((string) ($root->ISUPKey  ?? $root->isupKey  ?? ''));

            // Always ACK — accept even unknown devices so punches come through
            $device = $deviceId !== ''
                ? AttendanceDevice::where('device_identifier', $deviceId)->first()
                : null;

            if ($device === null) {
                $this->print("  [WARN] Device '{$deviceId}' not in DB — accepting anyway (add it to Attendance Devices)");
            } elseif (!empty($device->isup_key) && !hash_equals((string) $device->isup_key, $isupKey)) {
                $this->print("  [WARN] Device '{$deviceId}' ISUP key mismatch — accepting anyway");
            } else {
                $this->print("  [OK]  Device '{$deviceId}' registered (db id={$device->id})");
            }

            $meta['authenticated'] = true;
            $meta['deviceId']      = $deviceId ?: $id;
            $meta['deviceRowId']   = $device?->id;

            $this->send($meta['socket'], IsupFrame::registerAck($frame->sequence));

        } catch (\Throwable $e) {
            $this->print("  [ERR] Register parse error: " . $e->getMessage());
            // Still ACK so the device stays connected and sends punches
            $meta['authenticated'] = true;
            $meta['deviceId']      = $id;
            $this->send($meta['socket'], IsupFrame::registerAck($frame->sequence));
        }
    }

    private function onEvent(string $id, array &$meta, IsupFrame $frame): void
    {
        // ACK immediately so device never times out
        $this->send($meta['socket'], IsupFrame::eventAck($frame->sequence));

        if (empty($frame->data)) {
            return;
        }

        // Print raw punch info to terminal regardless of DB match
        $this->printPunch($meta['deviceId'] ?? $id, $frame->data);

        try {
            $this->handler->handle($frame->data, $meta['deviceRowId']);
        } catch (\Throwable $e) {
            $this->print("  [ERR] Failed to save event: " . $e->getMessage());
            Log::error('[ISUP] Event save failed: ' . $e->getMessage());
        }
    }

    // ── Output ────────────────────────────────────────────────────────────────

    private function printPunch(string $deviceId, string $xml): void
    {
        try {
            $clean = $this->stripNamespaces($xml);
            $root  = new SimpleXMLElement($clean);
            $ac    = $root->AccessControllerEvent;

            $employee = trim((string) ($ac->employeeNoString ?? '?'));
            $status   = trim((string) ($ac->attendanceStatus ?? '?'));
            $dt       = trim((string) ($root->dateTime ?? now()->toISOString()));
            $mode     = trim((string) ($ac->operateType ?? '?'));

            $this->print(sprintf(
                '  [PUNCH] device=%-12s  employee=%-8s  type=%-10s  mode=%-12s  time=%s',
                $deviceId, $employee, strtoupper($status), $mode, $dt
            ));
        } catch (\Throwable) {
            // XML parse failed — dump raw so nothing is hidden
            $this->print('  [PUNCH] (raw) ' . substr($xml, 0, 200));
        }
    }

    private function print(string $message): void
    {
        $ts = now()->format('H:i:s');
        ($this->out)("[{$ts}] {$message}");
        Log::info('[ISUP] ' . $message);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function send($socket, string $data): void
    {
        $total   = strlen($data);
        $written = 0;
        while ($written < $total) {
            $n = @fwrite($socket, substr($data, $written));
            if ($n === false || $n === 0) {
                break;
            }
            $written += $n;
        }
    }

    private function disconnect(string $id): void
    {
        if (!isset($this->clients[$id])) {
            return;
        }
        @fclose($this->clients[$id]['socket']);
        $label = $this->clients[$id]['deviceId'] ?? $id;
        $this->print("← Device disconnected: {$label}");
        unset($this->clients[$id]);
    }

    private function stripNamespaces(string $xml): string
    {
        return preg_replace('/\s+xmlns[^=]*="[^"]*"/', '', $xml) ?? $xml;
    }
}
