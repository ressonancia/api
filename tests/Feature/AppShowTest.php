<?php

use App\Models\App;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\SubstituteBindings;

test('user can see a single app', function () {
    $user = $this->login();

    $app = App::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->getJson(route('api.apps.show', $app->id));

    $this->assertEquals(
        (array) $response->getData(),
        $app->toArray()
    );

    $response->assertStatus(Response::HTTP_OK);
});

test('user cannot see a apps from another user', function () {
    $this->login();

    $anotherUser = User::factory()->create();

    $app = App::factory()->create([
        'user_id' => $anotherUser->id,
    ]);

    $this->getJson(route('api.apps.show', $app->id))
        ->assertForbidden();
});

test('user receives a 404 when quering for a non existent app', function () {
    $response = $this->getJson(route('api.apps.show', 1));
    $response->assertStatus(Response::HTTP_NOT_FOUND)
        ->assertJson([
            'message' => "No query results for model [App\Models\App] 1",
        ]);
});

test('user needs to be logged in to show app', function () {
    $this->withMiddleware(Authenticate::class);

    $this->getJson(route('api.apps.show', 1))->assertUnauthorized();
});

test('user needs to be verify email to show app', function () {
    $this->withMiddleware(EnsureEmailIsVerified::class);
    $this->withoutMiddleware(SubstituteBindings::class);

    $this->getJson(route('api.apps.show', 1))->assertForbidden();
});
