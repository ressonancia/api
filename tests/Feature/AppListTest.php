<?php

use App\Models\App;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Response;

test('user can list apps', function () {
    $user = $this->login();

    $app = App::factory()->create([
        'user_id' => $user->id
    ]);

    // This one should not be retrieved
    // belongs to another user
    App::factory()->create([
        'user_id' => $user->id + 1
    ]);

    $response = $this->getJson(route('api.apps.index'));

    $this->assertEquals(
        (array) $response->getData()->data[0],
        $app->toArray()
    );

    expect($response->getData()->data)->toHaveCount(1);
    $response->assertStatus(Response::HTTP_OK);
});

test('user needs to be logged in to list', function () {
    $this->withMiddleware(Authenticate::class);

    $this->getJson(route('api.apps.index'))->assertUnauthorized();
});

test('user needs to verify email to list', function () {
    $this->withMiddleware(EnsureEmailIsVerified::class);

    $this->getJson(route('api.apps.index'))->assertForbidden();
});