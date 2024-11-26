<?php

use App\Models\App;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('user can see a single app', function () {
    $app = App::factory()->create();

    $response = $this->getJson(route('api.apps.show', $app->id));

    $this->assertEquals(
        (array) $response->getData(),
        $app->toArray()
    );

    $response->assertStatus(Response::HTTP_OK);
});


test('user receives a 404 when quering for a non existent app', function () {
    $response = $this->getJson(route('api.apps.show', 1));
    $response->assertStatus(Response::HTTP_NOT_FOUND)
        ->assertJson([
            'message' => "No query results for model [App\Models\App] 1"
        ]);
});

test('user needs to be logged in to show app', function () {
    $this->withMiddleware(Authenticate::class);

    $this->getJson(route('api.apps.show', 1))->assertUnauthorized();
});