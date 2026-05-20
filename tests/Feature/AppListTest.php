<?php

use App\Models\App;
use App\Models\Organization;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Response;

test('user can list apps', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $app = App::factory()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    // This one should not be retrieved
    // belongs to another organization
    App::factory()->create([
        'user_id' => $user->id,
        'organization_id' => Organization::factory()->create()->id,
    ]);

    $response = $this->getJson(route('api.apps.index', [
        'organization' => $organization->id,
    ]));

    $this->assertEquals(
        (array) $response->getData()->data[0],
        $app->toArray()
    );

    expect($response->getData()->data)->toHaveCount(1);
    $response->assertStatus(Response::HTTP_OK);
});

test('user needs to be logged in to list', function () {
    $this->withMiddleware(Authenticate::class);
    $organization = Organization::factory()->create();

    $this->getJson(route('api.apps.index', [
        'organization' => $organization->id,
    ]))->assertUnauthorized();
});

test('user needs to verify email to list', function () {
    $this->withMiddleware(EnsureEmailIsVerified::class);
    $organization = Organization::factory()->create();

    $this->getJson(route('api.apps.index', [
        'organization' => $organization->id,
    ]))->assertForbidden();
});
