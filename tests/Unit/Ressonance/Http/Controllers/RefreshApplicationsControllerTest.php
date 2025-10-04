<?php

use App\Models\App;
use App\Ressonance\DatabaseApplicationProvider;
use Laravel\Reverb\ApplicationManager;
use Laravel\Reverb\Contracts\ApplicationProvider;

use function React\Async\await;

uses(Tests\ReverbTestCase::class);

it('can clean Applications drivers at the service container', function () {
    $this->mock(ApplicationManager::class, function ($mock) {
        $mock->shouldReceive('forgetDrivers')
            ->once();
    });

    $response = await($this->requestWithoutAppId('/refresh-applications', 'POST'));
    $decodedResponse = json_decode(
        $response->getBody()->getContents(),
        true
    );

    expect($response->getStatusCode())->toBe(200);
    expect($decodedResponse['status'])->toBe('Applications Refreshed');
});

it('can clean DatabaseApplicationProvider instance at the service container', function () {
    // save the instance in memory
    app(DatabaseApplicationProvider::class);

    $app = App::factory()->create([
        'user_id' => 1,
    ]);

    await($this->requestWithoutAppId('/refresh-applications', 'POST'));

    expect(app(ApplicationProvider::class)->all()->map->id())
        ->toContain($app->app_id);
});
