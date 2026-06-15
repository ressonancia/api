<?php

use App\Models\Organization;
use App\Models\OrganizationUser;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('user can create organization and becomes owner', function () {
    $user = $this->login();
    $preexistingOrganizationCount = $user->organizations()->count();

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

    expect($user->fresh()->organizations)->toHaveCount($preexistingOrganizationCount + 1);
});

test('user cannot create organization when reaching the configured limit', function () {
    config()->set('ressonance.max_organizations_per_user', 2);
    $maximumOrganizationsPerUser = config('ressonance.max_organizations_per_user');

    $user = $this->login();

    $organization = Organization::factory()->create();

    $user->organizations()->attach($organization->id, [
        'role' => Organization::ROLE_OWNER,
    ]);

    $response = $this->postJson(route('api.organizations.store'), [
        'name' => 'Overflow Organization',
    ]);

    $response->assertStatus(Response::HTTP_PRECONDITION_FAILED)
        ->assertJsonPath('message', "The user cannot have more than {$maximumOrganizationsPerUser} organizations");

    $this->assertDatabaseMissing(Organization::class, [
        'name' => 'Overflow Organization',
    ]);

    expect($user->fresh()->organizations)->toHaveCount($maximumOrganizationsPerUser);
});

test('organization creation throws exception if max organizations per user is not an integer', function () {
    $this->withoutExceptionHandling();
    config()->set('ressonance.max_organizations_per_user', 'not an integer');
    $this->login();

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Configuration "ressonance.max_organizations_per_user" must be an integer');

    $this->postJson(route('api.organizations.store'), [
        'name' => 'Error Organization',
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
