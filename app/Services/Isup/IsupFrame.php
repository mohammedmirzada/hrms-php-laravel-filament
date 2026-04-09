<?php

namespace App\Services\Isup;

/**
 * ISUP 5.0 binary frame parser / builder.
 *
 * Wire format (big-endian):
 *   [0-3]  Magic    = "ISUP"
 *   [4]    Version  = 5
 *   [5]    Reserved = 0
 *   [6-7]  MsgType  uint16
 *   [8-11] Sequence uint32
 *  [12-15] Timestamp uint32 (Unix)
 *  [16-19] DataLen  uint32
 *  [20+]   Data     (XML)
 */
class IsupFrame
{
    public const MAGIC        = 'ISUP';
    public const VERSION      = 5;
    public const HEADER_SIZE  = 20;

    // Device → Server
    public const MSG_REGISTER_REQ  = 0x0001;
    public const MSG_KEEPALIVE_REQ = 0x0003;
    public const MSG_EVENT         = 0x0005;

    // Server → Device
    public const MSG_REGISTER_ACK  = 0x0002;
    public const MSG_KEEPALIVE_ACK = 0x0004;
    public const MSG_EVENT_ACK     = 0x0006;

    public int    $msgType;
    public int    $sequence;
    public int    $timestamp;
    public string $data;

    private function __construct(int $msgType, string $data, int $sequence, int $timestamp)
    {
        $this->msgType   = $msgType;
        $this->data      = $data;
        $this->sequence  = $sequence;
        $this->timestamp = $timestamp;
    }

    /**
     * Try to extract one complete frame from a mutable buffer.
     * Returns the frame and advances the buffer; returns null if more data is needed.
     */
    public static function tryParse(string &$buffer): ?self
    {
        if (strlen($buffer) < self::HEADER_SIZE) {
            return null;
        }

        // Validate magic; if corrupt, scan forward for next "ISUP"
        if (substr($buffer, 0, 4) !== self::MAGIC) {
            $pos = strpos($buffer, self::MAGIC, 1);
            $buffer = $pos !== false ? substr($buffer, $pos) : '';
            return null;
        }

        $dataLen  = unpack('N', substr($buffer, 16, 4))[1];
        $totalLen = self::HEADER_SIZE + $dataLen;

        if (strlen($buffer) < $totalLen) {
            return null; // incomplete frame; wait for more bytes
        }

        $msgType   = unpack('n', substr($buffer, 6, 2))[1];
        $sequence  = unpack('N', substr($buffer, 8, 4))[1];
        $timestamp = unpack('N', substr($buffer, 12, 4))[1];
        $data      = $dataLen > 0 ? substr($buffer, self::HEADER_SIZE, $dataLen) : '';

        $buffer = substr($buffer, $totalLen);

        return new self($msgType, $data, $sequence, $timestamp);
    }

    // -------------------------------------------------------------------------
    // Helpers to build response frames
    // -------------------------------------------------------------------------

    public static function registerAck(int $sequence): string
    {
        $sessionId = bin2hex(random_bytes(8));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<ISUPRegisterResponse>'
            . '<statusCode>200</statusCode>'
            . '<statusString>OK</statusString>'
            . '<sessionID>' . $sessionId . '</sessionID>'
            . '</ISUPRegisterResponse>';
        return self::build(self::MSG_REGISTER_ACK, $xml, $sequence);
    }

    public static function registerReject(int $sequence, int $code = 401, string $msg = 'Unauthorized'): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<ISUPRegisterResponse>'
            . '<statusCode>' . $code . '</statusCode>'
            . '<statusString>' . htmlspecialchars($msg) . '</statusString>'
            . '</ISUPRegisterResponse>';
        return self::build(self::MSG_REGISTER_ACK, $xml, $sequence);
    }

    public static function keepaliveAck(int $sequence): string
    {
        return self::build(self::MSG_KEEPALIVE_ACK, '', $sequence);
    }

    public static function eventAck(int $sequence): string
    {
        return self::build(self::MSG_EVENT_ACK, '', $sequence);
    }

    private static function build(int $msgType, string $data, int $sequence): string
    {
        return self::MAGIC
            . chr(self::VERSION)
            . chr(0)
            . pack('n', $msgType)
            . pack('N', $sequence)
            . pack('N', time())
            . pack('N', strlen($data))
            . $data;
    }
}
