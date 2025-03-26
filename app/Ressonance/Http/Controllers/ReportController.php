<?php

namespace App\Ressonance\Http\Controllers;

use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Protocols\Pusher\Contracts\ChannelManager;
use Laravel\Reverb\Servers\Reverb\Http\Connection;
use Laravel\Reverb\Servers\Reverb\Http\Response;
use Psr\Http\Message\RequestInterface;
use Laravel\Reverb\Protocols\Pusher\Http\Controllers\Controller;

class ReportController extends Controller
{
    /**
     * Handle the request.
     */
    public function __invoke(RequestInterface $request, Connection $connection): Response
    {
        dump(
            app(ApplicationProvider::class)->all()->map(function($app) {
                return $app->id();
            })
        );

        dump(
            array_map(function ($channel) {
                return $channel->name();
            }, app(ChannelManager::class)->all())
        );

        // dump(
        //     array_map(function ($channel) {
        //         return $channel->identifier();
        //     }, app(ChannelConnectionManager::class)->all())
        // );

        // dump(app(ChannelConnectionManager::class)->all());

        return new Response((object) ['status' => 'Applications Dumped']);
    }
}
