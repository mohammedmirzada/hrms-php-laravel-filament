<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SimpleXMLElement;

class IsupListenCommand extends Command
{
    protected $signature   = 'isup:listen';
    protected $description = 'ISUP 5.0 TCP listener for Hikvision biometric device';

    private const HOST      = '0.0.0.0';
    private const PORT      = 7660;
    private const DEVICE_ID = 'DeviceLF1';
    private const ISUP_KEY  = 'LionsFortErbil';

    private const MAGIC       = 'ISUP';
    private const HEADER_SIZE = 20;
    private const MSG_REGISTER_REQ  = 0x0001;
    private const MSG_REGISTER_ACK  = 0x0002;
    private const MSG_KEEPALIVE_REQ = 0x0003;
    private const MSG_KEEPALIVE_ACK = 0x0004;
    private const MSG_EVENT         = 0x0005;
    private const MSG_EVENT_ACK     = 0x0006;

    private bool $running = false;

    public function handle(): int
    {
        $server = stream_socket_server(
            'tcp://' . self::HOST . ':' . self::PORT,
            $errno, $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
        );

        if ($server === false) {
            $this->error("Cannot bind port " . self::PORT . ": {$errstr}");
            return self::FAILURE;
        }

        stream_set_blocking($server, false);
        $this->running = true;

        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT,  fn () => $this->running = false);
            pcntl_signal(SIGTERM, fn () => $this->running = false);
        }

        $this->info('ISUP listener started on port ' . self::PORT . ' — Ctrl+C to stop');
        $this->line('  Expected device : ' . self::DEVICE_ID);
        $this->line('  Expected key    : ' . self::ISUP_KEY);
        $this->line($this->ts() . ' Waiting for device to connect...');

        $clients   = [];
        $lastBeat  = time();

        while ($this->running) {
            $read  = array_merge([$server], array_column($clients, 'socket'));
            $write = $except = null;

            if (@stream_select($read, $write, $except, 1) === false) {
                continue;
            }

            // Print a heartbeat every 30 seconds when no device is connected
            if (empty($clients) && time() - $lastBeat >= 30) {
                $this->line($this->ts() . ' Still waiting for device...');
                $lastBeat = time();
            }

            if (in_array($server, $read, true)) {
                $socket = @stream_socket_accept($server, 0, $peer);
                if ($socket) {
                    stream_set_blocking($socket, false);
                    $clients[$peer] = ['socket' => $socket, 'buffer' => ''];
                    $this->line($this->ts() . " → Connected: {$peer}");
                }
                unset($read[array_search($server, $read, true)]);
            }

            foreach ($clients as $peer => &$client) {
                if (!in_array($client['socket'], $read, true)) {
                    continue;
                }

                $chunk = fread($client['socket'], 65536);
                if ($chunk === false || $chunk === '') {
                    $this->line($this->ts() . " ← Disconnected: {$peer}");
                    @fclose($client['socket']);
                    unset($clients[$peer]);
                    continue;
                }

                $client['buffer'] .= $chunk;

                while (strlen($client['buffer']) >= self::HEADER_SIZE) {
                    $buf = &$client['buffer'];

                    if (substr($buf, 0, 4) !== self::MAGIC) {
                        $pos = strpos($buf, self::MAGIC, 1);
                        $buf = $pos !== false ? substr($buf, $pos) : '';
                        break;
                    }

                    $dataLen = unpack('N', substr($buf, 16, 4))[1];
                    if (strlen($buf) < self::HEADER_SIZE + $dataLen) {
                        break;
                    }

                    $msgType  = unpack('n', substr($buf, 6, 2))[1];
                    $sequence = unpack('N', substr($buf, 8, 4))[1];
                    $data     = $dataLen > 0 ? substr($buf, self::HEADER_SIZE, $dataLen) : '';
                    $buf      = substr($buf, self::HEADER_SIZE + $dataLen);

                    switch ($msgType) {

                        case self::MSG_REGISTER_REQ:
                            try {
                                $xml      = $this->stripNs($data);
                                $root     = new SimpleXMLElement(empty($xml) ? '<r/>' : $xml);
                                $deviceId = trim((string) ($root->deviceID ?? ''));
                                $key      = trim((string) ($root->ISUPKey  ?? ''));
                                $idMatch  = $deviceId === self::DEVICE_ID ? '✓' : '✗ expected ' . self::DEVICE_ID;
                                $keyMatch = $key      === self::ISUP_KEY  ? '✓' : '✗ expected ' . self::ISUP_KEY;
                                $this->line($this->ts() . "   REGISTER  device={$deviceId} [{$idMatch}]  key={$key} [{$keyMatch}]");
                            } catch (\Throwable) {
                                $this->line($this->ts() . "   REGISTER  (could not parse XML)");
                            }
                            $this->sendFrame($client['socket'], self::MSG_REGISTER_ACK,
                                '<?xml version="1.0" encoding="UTF-8"?><ISUPRegisterResponse>' .
                                '<statusCode>200</statusCode><statusString>OK</statusString>' .
                                '<sessionID>' . bin2hex(random_bytes(8)) . '</sessionID>' .
                                '</ISUPRegisterResponse>',
                                $sequence
                            );
                            break;

                        case self::MSG_KEEPALIVE_REQ:
                            $this->sendFrame($client['socket'], self::MSG_KEEPALIVE_ACK, '', $sequence);
                            break;

                        case self::MSG_EVENT:
                            $this->sendFrame($client['socket'], self::MSG_EVENT_ACK, '', $sequence);
                            try {
                                $xml      = $this->stripNs($data);
                                $root     = new SimpleXMLElement($xml);
                                $ac       = $root->AccessControllerEvent;
                                $employee = trim((string) ($ac->employeeNoString ?? '?'));
                                $status   = trim((string) ($ac->attendanceStatus ?? '?'));
                                $dt       = trim((string) ($root->dateTime       ?? '?'));
                                $mode     = trim((string) ($ac->operateType      ?? '?'));
                                $this->line(sprintf(
                                    '%s   PUNCH  employee=%-8s  type=%-10s  mode=%-12s  time=%s',
                                    $this->ts(), $employee, strtoupper($status), $mode, $dt
                                ));
                            } catch (\Throwable) {
                                $this->line($this->ts() . "   PUNCH  (raw) " . substr($data, 0, 300));
                            }
                            break;
                    }
                }
            }
            unset($client);
        }

        foreach ($clients as $c) {
            @fclose($c['socket']);
        }
        fclose($server);

        $this->info('Listener stopped.');
        return self::SUCCESS;
    }

    private function sendFrame($socket, int $msgType, string $data, int $sequence): void
    {
        $frame   = self::MAGIC . chr(5) . chr(0)
            . pack('n', $msgType)
            . pack('N', $sequence)
            . pack('N', time())
            . pack('N', strlen($data))
            . $data;
        $written = 0;
        while ($written < strlen($frame)) {
            $n = @fwrite($socket, substr($frame, $written));
            if (!$n) break;
            $written += $n;
        }
    }

    private function stripNs(string $xml): string
    {
        return preg_replace('/\s+xmlns[^=]*="[^"]*"/', '', $xml) ?? $xml;
    }

    private function ts(): string
    {
        return '[' . now()->format('H:i:s') . ']';
    }
}
