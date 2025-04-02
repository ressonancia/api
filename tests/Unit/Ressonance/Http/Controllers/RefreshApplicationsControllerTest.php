<?php

use Laravel\Reverb\ApplicationManager;

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
