<?php

use App\Models\App;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('user can list apps', function () {
    $app = App::factory()->create();

    $response = $this->getJson(route('api.apps.index'));

    $this->assertEquals(
        (array) $response->getData()->data[0],
        $app->toArray()
    );

    $response->assertStatus(Response::HTTP_OK);
});

test('user needs to be logged in to list', function () {
    $this->withMiddleware(Authenticate::class);

    $this->getJson(route('api.apps.index'))->assertUnauthorized();
});