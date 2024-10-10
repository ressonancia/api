<?php

use App\Models\App;
use App\Ressonance\DatabaseApplicationProvider;
use Laravel\Reverb\Application;
use Laravel\Reverb\Exceptions\InvalidApplication;

pest()->extend(Tests\TestCase::class);

beforeEach(function () {
    $this->appsCollection = App::factory()
        ->times(3)
        ->make()
        ->collect();

    $this->databaseApplicationProvider = new DatabaseApplicationProvider(
        $this->appsCollection
    );
});

it('can find an application by any attribute', function () {    
    $firstApp = $this->appsCollection->first();
    $firstFoundApp = $this->databaseApplicationProvider->find('app_id', $firstApp->app_id);

    $secondApp = $this->appsCollection[1];
    $secondFoundApp = $this->databaseApplicationProvider->find('app_key', $secondApp->app_key);

    expect($firstFoundApp->secret())->toEqual($firstApp->app_secret);
    expect($firstFoundApp)->toBeInstanceOf(Application::class);
    expect($secondFoundApp->secret())->toEqual($secondApp->app_secret);
    expect($secondFoundApp)->toBeInstanceOf(Application::class); 
});

it('throws an exception if app is not found', function () {    
    $this->databaseApplicationProvider->find('app_id', 'unknown');
})->throws(InvalidApplication::class);

it('can retrieve all applications', function () {    
    $applications = $this->databaseApplicationProvider->all();

    expect($applications)->toContainOnlyInstancesOf(Application::class);
});

it('can find by ID', function () {    
    $app = $this->appsCollection->first();
    $found = $this->databaseApplicationProvider->findById($app->app_id);

    expect($found->secret())->toEqual($app->app_secret);
    expect($found)->toBeInstanceOf(Application::class);
});

it('can find by key', function () {    
    $app = $this->appsCollection->first();
    $found = $this->databaseApplicationProvider->findByKey($app->app_key);

    expect($found->secret())->toEqual($app->app_secret);
    expect($found)->toBeInstanceOf(Application::class);
});
