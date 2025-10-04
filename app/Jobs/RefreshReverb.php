<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshReverb implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! config('ressonance.websocket_integration', true)) {
            Log::info('Websocket integration disabled by env');

            return;
        }

        $response = Http::post(config('ressonance.refresh_reverb_url'));
        Log::info('request endpoint response:'.$response->body());

    }
}
