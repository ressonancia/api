<?php

use App\Models\App;
use App\Models\Organization;
use Laravel\Reverb\ApplicationManager;

use function React\Async\await;

uses(Tests\ReverbTestCase::class);

it('can return debug report', function () {
    App::truncate();
    app()->make(ApplicationManager::class)->forgetDrivers();

    $app = App::factory()->create([
        'organization_id' => Organization::factory()->create()->id,
    ]);

    $response = await($this->requestWithoutAppId('/report'));
    $responseData = json_decode(
        $response->getBody()->getContents(),
        true
    );

    expect($response->getStatusCode())->toBe(200);
    expect($responseData['data']['apps'])
        ->toBeArray()
        ->toHaveCount(1)
        ->toContain(
            $app->app_id
        );
});
