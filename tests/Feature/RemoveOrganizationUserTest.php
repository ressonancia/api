<?php

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('organization owner can remove a member from organization', function () {
    $owner = $this->logIn();
    $organization = $owner->organizations()->first();
    $member = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => $member->email,
    ]);

    $anotherInvitationToKeep = Invitation::factory()->create();

    $organization->users()->attach($member->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $membership = OrganizationUser::where('organization_id', $organization->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->deleteJson(route('api.organization-users.destroy', [
        'organizationUser' => $membership->id,
    ]))->assertNoContent();

    $this->assertDatabaseMissing(OrganizationUser::class, [
        'id' => $membership->id,
    ]);

    $this->assertDatabaseMissing(Invitation::class, [
        'id' => $invitation->id,
    ]);

    $this->assertDatabaseHas(Invitation::class, [
        'id' => $anotherInvitationToKeep->id,
    ]);
});

test('organization admin can remove a member from organization', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $invitation = Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => $member->email,
    ]);

    $anotherInvitationToKeep = Invitation::factory()->create();

    $organization->users()->attach($admin->id, [
        'role' => Organization::ROLE_ADMIN,
    ]);

    $organization->users()->attach($member->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $this->logIn($admin);

    $membership = OrganizationUser::where('organization_id', $organization->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->deleteJson(route('api.organization-users.destroy', [
        'organizationUser' => $membership->id,
    ]))->assertNoContent();

    $this->assertDatabaseMissing(OrganizationUser::class, [
        'id' => $membership->id,
    ]);

    $this->assertDatabaseMissing(Invitation::class, [
        'id' => $invitation->id,
    ]);

    $this->assertDatabaseHas(Invitation::class, [
        'id' => $anotherInvitationToKeep->id,
    ]);
});

test('organization member cannot remove a member from organization', function () {
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

    $membership = OrganizationUser::where('organization_id', $organization->id)
        ->where('user_id', $anotherMember->id)
        ->firstOrFail();

    $this->deleteJson(route('api.organization-users.destroy', [
        'organizationUser' => $membership->id,
    ]))->assertForbidden();

    $this->assertDatabaseHas(OrganizationUser::class, [
        'id' => $membership->id,
    ]);
});

test('organization admin from another organization cannot remove a member from organization', function () {
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

    $membership = OrganizationUser::where('organization_id', $organization->id)
        ->where('user_id', $member->id)
        ->firstOrFail();

    $this->deleteJson(route('api.organization-users.destroy', [
        'organizationUser' => $membership->id,
    ]))->assertForbidden();

    $this->assertDatabaseHas(OrganizationUser::class, [
        'id' => $membership->id,
    ]);
});

test('user needs to be logged in to remove a member from organization', function () {
    $this->withMiddleware(Authenticate::class);

    $organizationUser = OrganizationUser::factory()->create();

    $this->deleteJson(route('api.organization-users.destroy', [
        'organizationUser' => $organizationUser->id,
    ]))->assertUnauthorized();
});

test('user cannot remove themselves from organization', function () {
    $owner = $this->logIn();
    $organization = $owner->organizations()->first();

    $membership = OrganizationUser::where('organization_id', $organization->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->deleteJson(route('api.organization-users.destroy', [
        'organizationUser' => $membership->id,
    ]))->assertStatus(Response::HTTP_PRECONDITION_FAILED)
        ->assertJson([
            'message' => 'The user cannot remove themselves from the organization.',
        ]);

    $this->assertDatabaseHas(OrganizationUser::class, [
        'id' => $membership->id,
    ]);
});

test('owner cannot be removed from organization', function () {
    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();
    $admin = User::factory()->create();

    $organization->users()->attach($admin->id, [
        'role' => Organization::ROLE_ADMIN,
    ]);

    $this->logIn($admin);

    $ownerMembership = OrganizationUser::where('organization_id', $organization->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->deleteJson(route('api.organization-users.destroy', [
        'organizationUser' => $ownerMembership->id,
    ]))->assertStatus(Response::HTTP_PRECONDITION_FAILED)
        ->assertJson([
            'message' => 'The owner cannot be removed from the organization.',
        ]);

    $this->assertDatabaseHas(OrganizationUser::class, [
        'id' => $ownerMembership->id,
        'role' => Organization::ROLE_OWNER,
    ]);
});
