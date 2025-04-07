<?php

use App\Jobs\RefreshReverb;
use Illuminate\Support\Facades\Log;

pest()->extend(Tests\TestCase::class);

It('can refresh reverb applications', function () {
    $this->mockRefreshAppsRequest();

    Log::shouldReceive('info')
        ->once()
        ->with('request endpoint response:{"status":"Applications Refreshed"}');

    $job = new RefreshReverb();
    $job->handle();
});
