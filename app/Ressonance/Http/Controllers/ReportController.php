<?php

namespace App\Ressonance\Http\Controllers;

use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Protocols\Pusher\Contracts\ChannelManager;
use Laravel\Reverb\Servers\Reverb\Http\Response;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\Controller;

class ReportController extends Controller
{
    /**
     * Handle the request.
     */
    public function __invoke(): Response
    {

        $apps = app(ApplicationProvider::class)->all()->map(function($app) {
            return $app->id();
        });

        return new Response((object) ['data' => [
            'apps' => $apps
        ]]);
    }
}
