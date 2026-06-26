<?php

use App\Models\App;
use App\Models\Organization;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Str;

test('user can see a single app', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $response = $this->getJson(route('api.apps.show', [
        'organization' => $organization->id,
        'app' => $app->id,
    ]));

    $this->assertEquals(
        (array) $response->getData(),
        $app->toArray()
    );

    $response->assertStatus(Response::HTTP_OK);
});

test('user cannot see apps from another organization', function () {
    $this->login();

    $anotherOrganization = Organization::factory()->create();

    $app = App::factory()->create([
        'organization_id' => $anotherOrganization->id,
    ]);

    $this->getJson(route('api.apps.show', [
        'organization' => $anotherOrganization->id,
        'app' => $app->id,
    ]))
        ->assertForbidden();
});

test('user cannot mix organization and app id', function () {
    $user = $this->login();

    $anotherOrganization = Organization::factory()->create();

    $app = App::factory()->create([
        'organization_id' => $anotherOrganization->id,
    ]);

    $this->getJson(route('api.apps.show', [
        'organization' => $user->organizations()->first()->id,
        'app' => $app->id,
    ]))->assertNotFound();
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

    $this->getJson(route('api.apps.show', [
        'organization' => Str::uuid()->toString(),
        'app' => 1,
    ]))->assertUnauthorized();
});

test('user needs to be verify email to show app', function () {
    $this->withMiddleware(EnsureEmailIsVerified::class);
    $this->withoutMiddleware(SubstituteBindings::class);

    $this->getJson(route('api.apps.show', [
        'organization' => Str::uuid()->toString(),
        'app' => 1,
    ]))->assertForbidden();
});
