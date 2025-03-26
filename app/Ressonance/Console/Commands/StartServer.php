<?php

namespace App\Ressonance\Console\Commands;

use Laravel\Reverb\Contracts\Logger;
use Laravel\Reverb\Loggers\CliLogger;
use App\Ressonance\Servers\Factory as ServerFactory;
use React\EventLoop\Loop;
use Laravel\Reverb\Servers\Reverb\Console\Commands\StartServer as ReverbStartServer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'ressonance:start')]
class StartServer extends ReverbStartServer
{
	protected $signature = 'ressonance:start
	            {--host= : The IP address the server should bind to}
                {--port= : The port the server should listen on}
                {--hostname= : The hostname the server is accessible from}
                {--debug : Indicates whether debug messages should be displayed in the terminal}';

	protected $description = 'Start Reverb serve with ressonance customizations';

    public function handle(): void
    {
        if ($this->option('debug')) {
            $this->laravel->instance(Logger::class, new CliLogger($this->output));
        }

        $config = $this->laravel['config']['reverb.servers.reverb'];

        $loop = Loop::get();

        $server = ServerFactory::make(
            $host = $this->option('host') ?: $config['host'],
            $port = $this->option('port') ?: $config['port'],
            $hostname = $this->option('hostname') ?: $config['hostname'],
            $config['max_request_size'] ?? 10_000,
            $config['options'] ?? [],
            loop: $loop
        );

        $this->ensureHorizontalScalability($loop);
        $this->ensureStaleConnectionsAreCleaned($loop);
        $this->ensureRestartCommandIsRespected($server, $loop, $host, $port);
        $this->ensurePulseEventsAreCollected($loop, $config['pulse_ingest_interval']);
        $this->ensureTelescopeEntriesAreCollected($loop, $config['telescope_ingest_interval'] ?? 15);

        $this->components->info('Starting '.($server->isSecure() ? 'secure ' : '')."server on {$host}:{$port}".(($hostname && $hostname !== $host) ? " ({$hostname})" : ''));

        $server->start();
    }
}
