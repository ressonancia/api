<?php

use App\Jobs\RefreshReverb;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

pest()->extend(Tests\TestCase::class);

It('can refresh reverb applications', function () {
    $this->mockRefreshAppsRequest();

    Log::shouldReceive('info')
        ->once()
        ->with('request endpoint response:{"status":"Applications Refreshed"}');

    $job = new RefreshReverb;
    $job->handle();
});

It('can skip refresh reverb applications by environment variable', function () {
    Config::set(
        'ressonance.websocket_integration',
        false
    );

    Log::shouldReceive('info')
        ->once()
        ->with('Websocket integration disabled by env');

    $job = new RefreshReverb;
    $job->handle();
});
