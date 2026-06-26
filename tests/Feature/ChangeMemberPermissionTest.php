<?php

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('organization owner can change a member role', function () {
    $owner = $this->logIn();
    $organization = $owner->organizations()->first();
    $member = User::factory()->create();

    $organization->users()->attach($member->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $membership = OrganizationUser::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->patchJson(route('api.organization-users.role.update', [
        'organizationUser' => $membership->id,
    ]), [
        'role' => Organization::ROLE_ADMIN,
    ])
        ->assertOk()
        ->assertJsonPath('id', $membership->id)
        ->assertJsonPath('organization_id', $organization->id)
        ->assertJsonPath('user_id', $member->id)
        ->assertJsonPath('role', Organization::ROLE_ADMIN);

    $this->assertDatabaseHas(OrganizationUser::class, [
        'id' => $membership->id,
        'role' => Organization::ROLE_ADMIN,
    ]);
});

test('organization admin can change a member role', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $organization->users()->attach($admin->id, [
        'role' => Organization::ROLE_ADMIN,
    ]);

    $organization->users()->attach($member->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $this->logIn($admin);

    $membership = OrganizationUser::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->patchJson(route('api.organization-users.role.update', [
        'organizationUser' => $membership->id,
    ]), [
        'role' => Organization::ROLE_ADMIN,
    ])
        ->assertOk()
        ->assertJsonPath('role', Organization::ROLE_ADMIN);

    $this->assertDatabaseHas(OrganizationUser::class, [
        'id' => $membership->id,
        'role' => Organization::ROLE_ADMIN,
    ]);
});

test('organization member cannot change a member role', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $anotherMember = User::factory()->create();

    $organization->users()->attach($member->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $organization->users()->attach($anotherMember->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $this->logIn($member);

    $membership = OrganizationUser::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $anotherMember->id)
        ->firstOrFail();

    $this->patchJson(route('api.organization-users.role.update', [
        'organizationUser' => $membership->id,
    ]), [
        'role' => Organization::ROLE_ADMIN,
    ])->assertForbidden();

    $this->assertDatabaseHas(OrganizationUser::class, [
        'id' => $membership->id,
        'role' => Organization::ROLE_MEMBER,
    ]);
});

test('organization admin from another organization cannot change a member role', function () {
    $admin = User::factory()->create();
    $adminOrganization = $admin->organizations()->first();

    $organization = Organization::factory()->create();
    $member = User::factory()->create();

    $adminOrganization->users()->updateExistingPivot($admin->id, [
        'role' => Organization::ROLE_ADMIN,
    ]);

    $organization->users()->attach($member->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $this->logIn($admin);

    $membership = OrganizationUser::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->patchJson(route('api.organization-users.role.update', [
        'organizationUser' => $membership->id,
    ]), [
        'role' => Organization::ROLE_ADMIN,
    ])->assertForbidden();

    $this->assertDatabaseHas(OrganizationUser::class, [
        'id' => $membership->id,
        'role' => Organization::ROLE_MEMBER,
    ]);
});

test('user needs to be logged in to change a member role', function () {
    $this->withMiddleware(Authenticate::class);

    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();
    $member = User::factory()->create();

    $organization->users()->attach($member->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $membership = OrganizationUser::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->patchJson(route('api.organization-users.role.update', [
        'organizationUser' => $membership->id,
    ]), [
        'role' => Organization::ROLE_ADMIN,
    ])->assertUnauthorized();
});

test('role is required and must be admin or member', function () {
    $owner = $this->logIn();
    $organization = $owner->organizations()->first();
    $member = User::factory()->create();

    $organization->users()->attach($member->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $membership = OrganizationUser::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->patchJson(route('api.organization-users.role.update', [
        'organizationUser' => $membership->id,
    ]), [])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['role'])
        ->assertJsonFragment([
            'role' => ['The role field is required.'],
        ]);

    $this->patchJson(route('api.organization-users.role.update', [
        'organizationUser' => $membership->id,
    ]), [
        'role' => Organization::ROLE_OWNER,
    ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['role'])
        ->assertJsonFragment([
            'role' => ['The selected role is invalid.'],
        ]);
});

test('owner role cannot be changed', function () {
    $owner = $this->logIn();
    $organization = $owner->organizations()->first();

    $ownerMembership = OrganizationUser::query()
        ->where('organization_id', $organization->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->patchJson(route('api.organization-users.role.update', [
        'organizationUser' => $ownerMembership->id,
    ]), [
        'role' => Organization::ROLE_ADMIN,
    ])
        ->assertStatus(Response::HTTP_PRECONDITION_FAILED)
        ->assertJson([
            'message' => 'The owner role cannot be changed.',
        ]);

    $this->assertDatabaseHas(OrganizationUser::class, [
        'id' => $ownerMembership->id,
        'role' => Organization::ROLE_OWNER,
    ]);
});
