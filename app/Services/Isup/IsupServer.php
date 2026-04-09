<?php

namespace App\Services\Isup;

use App\Models\AttendanceDevice;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

/**
 * Non-blocking TCP server for ISUP 5.0.
 *
 * Uses stream_socket_server + stream_select so multiple Hikvision devices
 * can maintain persistent connections simultaneously.
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
    private bool $running = false;

    public function __construct(AttendanceEventHandler $handler)
    {
        $this->handler = $handler;
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

        Log::info("[ISUP] Listening on {$host}:{$port} (PID=" . getmypid() . ')');

        while ($this->running) {
            $read    = array_merge([$this->server], array_column($this->clients, 'socket'));
            $write   = null;
            $except  = null;

            // 1-second timeout so signal handlers (pcntl) are checked regularly
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
                    Log::info("[ISUP] Connected: {$id}");
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

        foreach ($this->clients as $id => $_) {
            $this->disconnect($id);
        }

        fclose($this->server);
        Log::info('[ISUP] Server stopped.');
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
                Log::debug("[ISUP] Keepalive ← {$id}");
                break;

            case IsupFrame::MSG_EVENT:
                $this->onEvent($id, $meta, $frame);
                break;

            default:
                Log::debug('[ISUP] Unknown msgType 0x' . dechex($frame->msgType) . " from {$id}");
        }
    }

    // ── Handlers ──────────────────────────────────────────────────────────────

    private function onRegister(string $id, array &$meta, IsupFrame $frame): void
    {
        try {
            $xml = $this->stripNamespaces($frame->data);
            $root = new SimpleXMLElement(empty($xml) ? '<ISUPRegister/>' : $xml);

            $deviceId = trim((string) ($root->deviceID ?? $root->DeviceID ?? ''));
            $isupKey  = trim((string) ($root->ISUPKey  ?? $root->isupKey  ?? ''));

            // Look up device by device_identifier column
            $device = $deviceId !== ''
                ? AttendanceDevice::where('device_identifier', $deviceId)->first()
                : null;

            if ($device === null) {
                Log::warning("[ISUP] Unknown device_identifier '{$deviceId}' from {$id}");
                $this->send($meta['socket'], IsupFrame::registerReject($frame->sequence, 404, 'Device not found'));
                return;
            }

            // Validate ISUP key only when one is stored on the device record
            if (!empty($device->isup_key) && !hash_equals((string) $device->isup_key, $isupKey)) {
                Log::warning("[ISUP] Bad ISUP key for device '{$deviceId}' from {$id}");
                $this->send($meta['socket'], IsupFrame::registerReject($frame->sequence, 401, 'Unauthorized'));
                return;
            }

            $meta['authenticated'] = true;
            $meta['deviceId']      = $deviceId;
            $meta['deviceRowId']   = $device->id;

            $this->send($meta['socket'], IsupFrame::registerAck($frame->sequence));
            Log::info("[ISUP] Registered device '{$deviceId}' (id={$device->id}) from {$id}");
        } catch (\Throwable $e) {
            Log::error("[ISUP] Register error: " . $e->getMessage());
            $this->send($meta['socket'], IsupFrame::registerReject($frame->sequence, 500, 'Server error'));
        }
    }

    private function onEvent(string $id, array &$meta, IsupFrame $frame): void
    {
        // Always ACK first so device doesn't time out
        $this->send($meta['socket'], IsupFrame::eventAck($frame->sequence));

        if (!$meta['authenticated']) {
            Log::warning("[ISUP] Event from unauthenticated {$id}, ignoring.");
            return;
        }

        if (empty($frame->data)) {
            return;
        }

        try {
            $this->handler->handle($frame->data, $meta['deviceRowId']);
        } catch (\Throwable $e) {
            Log::error("[ISUP] Event handler exception: " . $e->getMessage());
        }
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
        Log::info("[ISUP] Disconnected: {$label}");
        unset($this->clients[$id]);
    }

    private function stripNamespaces(string $xml): string
    {
        return preg_replace('/\s+xmlns[^=]*="[^"]*"/', '', $xml) ?? $xml;
    }
}
