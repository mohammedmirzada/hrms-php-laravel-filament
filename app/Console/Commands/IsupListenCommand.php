<?php

namespace App\Console\Commands;

use App\Services\Isup\AttendanceEventHandler;
use App\Services\Isup\IsupServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class IsupListenCommand extends Command
{
    protected $signature   = 'isup:listen
                                {--host=0.0.0.0 : IP address to bind}
                                {--port=7660    : TCP port to listen on}';

    protected $description = 'Start the ISUP 5.0 TCP listener for Hikvision biometric devices';

    public function handle(): int
    {
        $host = (string) $this->option('host');
        $port = (int)    $this->option('port');

        $server = new IsupServer(
            new AttendanceEventHandler(),
            fn (string $msg) => $this->line($msg),
        );

        // Graceful shutdown on SIGINT / SIGTERM
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT,  static fn () => $server->stop());
            pcntl_signal(SIGTERM, static fn () => $server->stop());
        }

        $this->info("ISUP 5.0 listener starting on {$host}:{$port} (Ctrl+C to stop)");

        try {
            $server->start($host, $port);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            Log::critical('[ISUP] Fatal: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('ISUP listener stopped.');
        return self::SUCCESS;
    }
}
