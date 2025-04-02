<?php

namespace App\Ressonance\Http\Controllers;

use Laravel\Reverb\ApplicationManager;
use Laravel\Reverb\Servers\Reverb\Http\Response;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\Controller;

class RefreshApplicationsController extends Controller
{
    /**
     * Handle the request.
     */
    public function __invoke(): Response
    {
        app()->make(ApplicationManager::class)->forgetDrivers();

        return new Response((object) ['status' => 'Applications Refreshed']);
    }
}
