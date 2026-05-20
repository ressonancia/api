<?php

use App\Jobs\RefreshReverb;
use App\Models\App;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use SendKit\Laravel\Facades\SendKit;

test('admins can update organizations', function () {
    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();

    $admin = $this->login();
    $organization->users()->attach($admin->id, ['role' => Organization::ROLE_ADMIN]);

    $this->patchJson(route('api.organizations.update', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Updated Organization Name',
    ])->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('name', 'Updated Organization Name');

    $this->assertDatabaseHas('organizations', [
        'id' => $organization->id,
        'name' => 'Updated Organization Name',
    ]);
});

test('members cannot update organizations', function () {
    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();

    $member = $this->login();
    $organization->users()->attach($member->id, ['role' => Organization::ROLE_MEMBER]);

    $this->patchJson(route('api.organizations.update', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Updated Organization Name',
    ])->assertStatus(Response::HTTP_FORBIDDEN);
});

test('owner can delete organization and apps', function () {
    Queue::fake();
    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $app = App::factory()->create([
        'user_id' => $owner->id,
        'organization_id' => $organization->id,
    ]);

    $this->deleteJson(route('api.organizations.destroy', [
        'organization' => $organization->id,
    ]))->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('organizations', [
        'id' => $organization->id,
    ]);

    $this->assertDatabaseMissing('apps', [
        'id' => $app->id,
        'deleted_at' => null,
    ]);

    Queue::assertPushed(RefreshReverb::class);
});

test('admin cannot delete an organization they do not own', function () {
    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();

    $admin = $this->login();
    $organization->users()->attach($admin->id, ['role' => Organization::ROLE_ADMIN]);

    $this->deleteJson(route('api.organizations.destroy', [
        'organization' => $organization->id,
    ]))->assertStatus(Response::HTTP_FORBIDDEN);
});

test('owner and admins can invite organization members', function () {
    Notification::fake();

    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $response = $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'email' => 'invitee@ressonance.com',
        'role' => Organization::ROLE_MEMBER,
    ]);

    $response->assertStatus(Response::HTTP_CREATED);

    $invitation = OrganizationInvitation::first();

    expect($invitation)->not->toBeNull();

    $this->assertDatabaseHas('organization_invitations', [
        'id' => $invitation->id,
        'organization_id' => $organization->id,
        'email' => 'invitee@ressonance.com',
        'role' => Organization::ROLE_MEMBER,
    ]);

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class);
});

test('members cannot invite organization members', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();

    $member = $this->login();
    $organization->users()->attach($member->id, ['role' => Organization::ROLE_MEMBER]);

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'email' => 'invitee@ressonance.com',
        'role' => Organization::ROLE_MEMBER,
    ])->assertStatus(Response::HTTP_FORBIDDEN);

    Notification::assertNothingSent();
});

test('accepting an invitation validates email ownership and verifies the account', function () {
    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();

    $invitedUser = User::factory()->unverified()->create([
        'email' => 'invitee@ressonance.com',
    ]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'invited_by_user_id' => $owner->id,
        'email' => $invitedUser->email,
        'role' => Organization::ROLE_ADMIN,
    ]);

    $this->login($invitedUser);

    $this->postJson(route('api.organizations.invitations.accept', [
        'invitation' => $invitation->id,
    ]))->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $invitedUser->id,
        'role' => Organization::ROLE_ADMIN,
    ]);

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($invitedUser->fresh()->email_verified_at)->not->toBeNull();
});

test('invitation endpoint validates the email using enhanced validation rules', function () {
    if (! class_exists(SendKit::class)) {
        $this->markTestSkipped('SendKit package is not available in this environment.');
    }

    config([
        'mail.default' => 'sendkit',
        'app.enable_email_enhanced_verification' => true,
    ]);

    SendKit::shouldReceive('validateEmail')
        ->once()
        ->with('blocked@ressonance.com')
        ->andReturn([
            'should_block' => true,
        ]);

    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'email' => 'blocked@ressonance.com',
        'role' => Organization::ROLE_MEMBER,
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email']);
});
