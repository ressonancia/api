<?php

namespace App\Ressonance\Servers;

use App\Ressonance\Http\Controllers\RefreshApplicationsController;
use App\Ressonance\Http\Controllers\ReportController;
use Laravel\Reverb\Servers\Reverb\Factory as ReverbFactory;
use Laravel\Reverb\Servers\Reverb\Http\Route;
use Symfony\Component\Routing\RouteCollection;

class Factory extends ReverbFactory
{
    protected static function pusherRoutes(): RouteCollection
    {
		
        $routes = parent::pusherRoutes();

        $routes->add('refresh_applications', Route::get('/refresh-applications', new RefreshApplicationsController));
        $routes->add('report', Route::get('/report', new ReportController));

        return $routes;
    }
}
