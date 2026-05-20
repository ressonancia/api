<?php

use App\Models\App;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\SubstituteBindings;

test('user can see a single app', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $app = App::factory()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    $response = $this->getJson(route('api.apps.show', [
        'organization' => $organization->id,
        'app' => $app->id,
    ]));

    $response->assertJsonPath('id', $app->id);
    $response->assertJsonPath('organization_id', $organization->id);
    $response->assertJsonPath('app_name', $app->app_name);

    $response->assertStatus(Response::HTTP_OK);
});

test('user cannot see a apps from another organization', function () {
    $this->login();

    $anotherUser = User::factory()->create();
    $organization = $anotherUser->organizations()->first();

    $app = App::factory()->create([
        'user_id' => $anotherUser->id,
        'organization_id' => $organization->id,
    ]);

    $this->getJson(route('api.apps.show', [
        'organization' => $organization->id,
        'app' => $app->id,
    ]))
        ->assertForbidden();
});

test('user receives a 404 when quering for a non existent app', function () {
    $organization = Organization::factory()->create();
    $response = $this->getJson(route('api.apps.show', [
        'organization' => $organization->id,
        'app' => 1,
    ]));
    $response->assertStatus(Response::HTTP_NOT_FOUND)
        ->assertJson([
            'message' => "No query results for model [App\Models\App] 1",
        ]);
});

test('user needs to be logged in to show app', function () {
    $this->withMiddleware(Authenticate::class);
    $organization = Organization::factory()->create();

    $this->getJson(route('api.apps.show', [
        'organization' => $organization->id,
        'app' => 1,
    ]))->assertUnauthorized();
});

test('user needs to be verify email to show app', function () {
    $this->withMiddleware(EnsureEmailIsVerified::class);
    $this->withoutMiddleware(SubstituteBindings::class);
    $organization = Organization::factory()->create();

    $this->getJson(route('api.apps.show', [
        'organization' => $organization->id,
        'app' => 1,
    ]))->assertForbidden();
});
