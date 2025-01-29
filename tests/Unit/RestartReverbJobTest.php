<?php

use App\Jobs\RestartReverb;
use Illuminate\Support\Facades\Artisan;

It('can call Artisan command to restart reverb', function () {
    
    Artisan::shouldReceive('call')
        ->once()
        ->with('reverb:restart');

    $job = new RestartReverb();
    $job->handle();

});
