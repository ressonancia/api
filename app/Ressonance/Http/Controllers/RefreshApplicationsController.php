<?php

namespace App\Ressonance\Http\Controllers;

use Laravel\Reverb\ApplicationManager;
use Laravel\Reverb\Servers\Reverb\Http\Connection;
use Laravel\Reverb\Servers\Reverb\Http\Response;
use Psr\Http\Message\RequestInterface;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\Controller;

class RefreshApplicationsController extends Controller
{
    /**
     * Handle the request.
     */
    public function __invoke(RequestInterface $request, Connection $connection): Response
    {
        app()->make(ApplicationManager::class)->forgetDrivers();

        return new Response((object) ['status' => 'Applications Loaded']);
    }
}
