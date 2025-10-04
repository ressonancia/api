<?php

namespace App\Ressonance\Http\Controllers;

use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\Controller;
use Laravel\Reverb\Servers\Reverb\Http\Response;

class ReportController extends Controller
{
    /**
     * Handle the request.
     */
    public function __invoke(): Response
    {

        $apps = app(ApplicationProvider::class)->all()->map(function ($app) {
            return $app->id();
        });

        return new Response((object) ['data' => [
            'apps' => $apps,
        ]]);
    }
}
