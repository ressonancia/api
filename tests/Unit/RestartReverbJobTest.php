<?php

use App\Jobs\RestartReverb;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

pest()->extend(Tests\TestCase::class);

It('can call Artisan command to restart reverb', function () {
    
    Carbon::setTestNow();

    Cache::shouldReceive('forever')
        ->once()
        ->with(
            'laravel:reverb:restart',
            Carbon::now()->getTimestamp()
        );

    $job = new RestartReverb();
    $job->handle();

});
