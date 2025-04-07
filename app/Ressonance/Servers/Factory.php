<?php

namespace App\Ressonance\Servers;

use App\Ressonance\Http\Controllers\RefreshApplicationsController;
use App\Ressonance\Http\Controllers\ReportController;
use Laravel\Reverb\Servers\Reverb\Factory as ReverbFactory;
use Laravel\Reverb\Servers\Reverb\Http\Route;
use Symfony\Component\Routing\RouteCollection;

class Factory extends ReverbFactory
{
    protected static function pusherRoutes(string $path): RouteCollection
    {
		
        $routes = parent::pusherRoutes($path);

        $routes->add('refresh_applications', Route::post('/refresh-applications', new RefreshApplicationsController));
        $routes->add('report', Route::get('/report', new ReportController));

        return $routes;
    }
}
