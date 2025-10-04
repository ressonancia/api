<?php

use App\Models\App;
use App\Ressonance\DatabaseApplicationProvider;
use App\Ressonance\DynamicDatabaseApplicationProxyProvider;
use Laravel\Reverb\Application;
use Laravel\Reverb\Exceptions\InvalidApplication;

pest()->extend(Tests\TestCase::class);

beforeEach(function () {
    $this->appsCollection = App::factory()
        ->times(3)
        ->create(['user_id' => 1])
        ->collect();

    $this->databaseApplicationProvider
        = new DynamicDatabaseApplicationProxyProvider(collect());
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

it('The method all is a proxy', function () {
    $this->mock(DatabaseApplicationProvider::class, function ($mock) {
        $mock->shouldReceive('all')
            ->once()
            ->andReturn(collect());
    });

    $this->databaseApplicationProvider->all();
});

it('The method findById is a proxy', function () {
    $application = buildApp();

    $this->mock(DatabaseApplicationProvider::class, function ($mock) use ($application) {
        $mock->shouldReceive('findById')
            ->with('my-id')
            ->once()
            ->andReturn($application);
    });

    expect($this->databaseApplicationProvider->findById('my-id')->id())
        ->toBe($application->id());
});

it('The method findByKey is a proxy', function () {
    $application = buildApp();

    $this->mock(DatabaseApplicationProvider::class, function ($mock) use ($application) {
        $mock->shouldReceive('findByKey')
            ->with('my-id')
            ->once()
            ->andReturn($application);
    });

    expect($this->databaseApplicationProvider->findByKey('my-id')->id())
        ->toBe($application->id());
});

it('The method find is a proxy', function () {
    $application = buildApp();

    $this->mock(DatabaseApplicationProvider::class, function ($mock) use ($application) {
        $mock->shouldReceive('find')
            ->with('id', 'my-id')
            ->once()
            ->andReturn($application);
    });

    expect($this->databaseApplicationProvider->find('id', 'my-id')->id())
        ->toBe($application->id());
});

function buildApp()
{
    return new Application(
        id: 'app-1',
        key: 'my-key',
        secret: 'super-secret',
        pingInterval: 30,
        activityTimeout: 120,
        allowedOrigins: ['https://example.com'],
        maxMessageSize: 1024 * 10,
        options: ['debug' => true]);
}
