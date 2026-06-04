<?php

use App\Models\Organization;
use App\Models\OrganizationUser;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('user can create organization and becomes owner', function () {
    $user = $this->login();

    $response = $this->postJson(route('api.organizations.store'), [
        'name' => 'Ressonance Labs',
    ]);

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('name', 'Ressonance Labs');

    $organizationId = $response->json('id');

    $this->assertDatabaseHas(Organization::class, [
        'id' => $organizationId,
        'name' => 'Ressonance Labs',
    ]);

    $this->assertDatabaseHas(OrganizationUser::class, [
        'organization_id' => $organizationId,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
});

test('user needs to be logged in to create organization', function () {
    $this->withMiddleware(Authenticate::class);

    $this->postJson(route('api.organizations.store'), [
        'name' => 'Ressonance Labs',
    ])->assertUnauthorized();
});

test('user needs to give a valid name to create organization', function () {
    $this->login();

    $this->postJson(route('api.organizations.store'), [])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['name'])
        ->assertJsonFragment([
            'name' => ['The name field is required.'],
        ]);
});
