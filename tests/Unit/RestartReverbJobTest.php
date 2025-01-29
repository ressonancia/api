<?php

use App\Jobs\RestartReverb;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

pest()->extend(Tests\TestCase::class);

It('can restart reverb', function () {
    
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
