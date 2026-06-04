<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

test('organization owner can update organization name', function () {
    /**
     * withOrganizationCreation fix race condition with withoutOrganizationCreation
     * It is called in other test and needs to be reset
     */
    User::withOrganizationCreation();
    $user = $this->login();
    $organization = $user->organizations()->first();

    $response = $this->patchJson(route('api.organizations.update', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Updated Organization Name',
    ]);

    $response->assertStatus(Response::HTTP_OK)->assertJson([
        'id' => $organization->id,
        'name' => 'Updated Organization Name',
    ]);

    $this->assertDatabaseHas(Organization::class, [
        'id' => $organization->id,
        'name' => 'Updated Organization Name',
    ]);
});

test('organization admin can update organization name', function () {
    $organization = Organization::factory()->create([
        'name' => 'Original Name',
    ]);

    $admin = User::factory()->create();

    $notUpdatedOrganization = $admin->organizations()->first();

    $organization->users()->attach($admin->id, [
        'role' => Organization::ROLE_ADMIN,
    ]);

    $this->logIn($admin);

    $response = $this->patchJson(route('api.organizations.update', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Updated By Admin',
    ]);

    $response->assertStatus(Response::HTTP_OK)->assertJson([
        'id' => $organization->id,
        'name' => 'Updated By Admin',
    ]);

    $this->assertDatabaseHas(Organization::class, [
        'id' => $organization->id,
        'name' => 'Updated By Admin',
    ]);

    $this->assertDatabaseHas(Organization::class, [
        'id' => $notUpdatedOrganization->id,
        'name' => $notUpdatedOrganization->name,
    ]);
});

test('organization member with member role cannot update organization name', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $this->logIn($user);

    $this->patchJson(route('api.organizations.update', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Should Not Update',
    ])->assertNotFound();

    $this->assertDatabaseMissing(Organization::class, [
        'id' => $organization->id,
        'name' => 'Should Not Update',
    ]);
});

test('only name can be updated on organization', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $originalId = $organization->id;

    $response = $this->patchJson(route('api.organizations.update', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Only Name Updated',
        'id' => (string) Str::uuid(),
    ]);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('id', $originalId)
        ->assertJsonPath('name', 'Only Name Updated');

    $this->assertDatabaseHas(Organization::class, [
        'id' => $originalId,
        'name' => 'Only Name Updated',
    ]);
});

test('user needs to be logged in to update organization', function () {
    $this->withMiddleware(Authenticate::class);

    $organization = Organization::factory()->create();

    $this->patchJson(route('api.organizations.update', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Should Not Update',
    ])->assertUnauthorized();
});

test('name is required to update organization', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $this->patchJson(route('api.organizations.update', [
        'organization' => $organization->id,
    ]), [])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['name'])
        ->assertJsonFragment([
            'name' => ['The name field is required.'],
        ]);
});
