<?php

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

test('organization owner can invite a member by name and email', function () {
    Carbon::setTestNow('2026-05-26 15:00:00');
    Notification::fake();

    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $response = $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'New Member',
        'email' => 'new-member@example.com',
        'role' => Organization::ROLE_USER,
    ]);

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('organization_id', $organization->id)
        ->assertJsonPath('inviter_id', $owner->id)
        ->assertJsonPath('name', 'New Member')
        ->assertJsonPath('email', 'new-member@example.com')
        ->assertJsonPath('role', Organization::ROLE_USER);

    $invitationId = $response->json('id');

    $this->assertDatabaseHas('invitations', [
        'id' => $invitationId,
        'organization_id' => $organization->id,
        'inviter_id' => $owner->id,
        'name' => 'New Member',
        'email' => 'new-member@example.com',
        'role' => Organization::ROLE_USER,
    ]);

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class, function ($notification, $channels, $notifiable) use ($invitationId) {
        expect($notifiable->routes['mail'])->toBe('new-member@example.com');
        expect($notification->invitation->id)->toBe($invitationId);
        expect($notification->invitation->name)->toBe('New Member');
        expect($notification->invitation->role)->toBe(Organization::ROLE_USER);
        expect($notification->invitationUrl)->toStartWith(config('app.spa_url').'/email-invitation?');

        $spaQuery = [];
        parse_str(parse_url($notification->invitationUrl, PHP_URL_QUERY), $spaQuery);
        expect($spaQuery)->toHaveKey('route');

        $encodedRoute = strtr($spaQuery['route'], '-_', '+/');
        $paddedEncodedRoute = str_pad($encodedRoute, (int) (ceil(strlen($encodedRoute) / 4) * 4), '=', STR_PAD_RIGHT);
        $signedRoute = base64_decode($paddedEncodedRoute, true);
        expect($signedRoute)->not->toBeFalse();

        $routeQuery = [];
        parse_str(parse_url($signedRoute, PHP_URL_QUERY), $routeQuery);

        expect($routeQuery)->toHaveKey('expires');
        expect((int) $routeQuery['expires'])->toBe($notification->invitation->expires_at->timestamp);

        return true;
    });
});

test('organization admin can invite a member by name and email', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->create();

    $organization->users()->attach($admin->id, [
        'id' => (string) Str::uuid(),
        'role' => Organization::ROLE_ADMIN,
    ]);

    $this->logIn($admin);

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Invite Admin',
        'email' => 'invite-admin@example.com',
        'role' => Organization::ROLE_ADMIN,
    ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('organization_id', $organization->id)
        ->assertJsonPath('inviter_id', $admin->id)
        ->assertJsonPath('name', 'Invite Admin')
        ->assertJsonPath('email', 'invite-admin@example.com')
        ->assertJsonPath('role', Organization::ROLE_ADMIN);

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class);
});

test('organization user role cannot invite members', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user->id, [
        'id' => (string) Str::uuid(),
        'role' => Organization::ROLE_USER,
    ]);

    $this->logIn($user);

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Forbidden User',
        'email' => 'forbidden@example.com',
        'role' => Organization::ROLE_USER,
    ])->assertForbidden();

    $this->assertDatabaseMissing('invitations', [
        'organization_id' => $organization->id,
        'email' => 'forbidden@example.com',
    ]);

    Notification::assertNothingSent();
});

test('user needs to be logged in to invite a member', function () {
    $this->withMiddleware(Authenticate::class);

    $organization = Organization::factory()->create();

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Unauthorized User',
        'email' => 'unauthorized@example.com',
        'role' => Organization::ROLE_USER,
    ])->assertUnauthorized();
});

test('email is required to invite a member', function () {
    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'No Email',
        'role' => Organization::ROLE_USER,
    ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email'])
        ->assertJsonFragment([
            'email' => ['The email field is required.'],
        ]);
});

test('name is required to invite a member', function () {
    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'email' => 'no-name@example.com',
        'role' => Organization::ROLE_USER,
    ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['name'])
        ->assertJsonFragment([
            'name' => ['The name field is required.'],
        ]);
});

test('role is required to invite a member', function () {
    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'No Role',
        'email' => 'no-role@example.com',
    ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['role'])
        ->assertJsonFragment([
            'role' => ['The role field is required.'],
        ]);
});

test('removes previous invitations for the same organization and user before creating a new one', function () {
    Notification::fake();

    $owner = $this->login();
    $organization = $owner->organizations()->first();
    $otherOrganization = Organization::factory()->create();

    $expiredInvitation = $organization->invitations()->create([
        'inviter_id' => $owner->id,
        'name' => 'Expired Member',
        'email' => 'new-member@example.com',
        'role' => Organization::ROLE_USER,
        'expires_at' => now()->addHour(),
        'joined_at' => null,
    ]);

    $response = $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Replacement Member',
        'email' => 'new-member@example.com',
        'role' => Organization::ROLE_ADMIN,
    ])->assertStatus(Response::HTTP_CREATED);

    $newInvitationId = $response->json('id');

    $this->assertDatabaseMissing(Invitation::class, ['id' => $expiredInvitation->id]);
    $this->assertDatabaseHas(Invitation::class, ['id' => $newInvitationId]);

    expect(Invitation::query()
        ->where('organization_id', $organization->id)
        ->where('email', 'new-member@example.com')
        ->count())->toBe(1);

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class);
});

test('throws when invitation expiration hours config is not an integer', function () {
    $this->withoutExceptionHandling();
    config()->set('ressonance.organization_invitation_expiration_hours', 'not-a-number');

    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Invalid organization invitation expiration hours. Should be an integer');

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'New Member',
        'email' => 'new-member@example.com',
        'role' => Organization::ROLE_USER,
    ]);
});

test('accept is rate limited to five requests per minute', function () {
    $inviter = User::factory()->create();
    $organization = $inviter->organizations()->first();
    $invitation = $organization->invitations()->create([
        'inviter_id' => $inviter->id,
        'name' => 'Rate Limited Member',
        'email' => 'rate-limited@example.com',
        'role' => Organization::ROLE_USER,
        'expires_at' => now()->addHour(),
        'joined_at' => now(),
    ]);

    $acceptUrl = URL::temporarySignedRoute(
        'api.invitations.accept',
        now()->addHour(),
        ['invitation' => $invitation->id]
    );

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson($acceptUrl)->assertStatus(Response::HTTP_PRECONDITION_FAILED);
    }

    $this->postJson($acceptUrl)->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
});

test('accept returns precondition failed when invitation was already joined', function () {
    $inviter = User::factory()->create();
    $organization = $inviter->organizations()->first();
    $invitation = $organization->invitations()->create([
        'inviter_id' => $inviter->id,
        'name' => 'Joined Member',
        'email' => 'joined@example.com',
        'role' => Organization::ROLE_USER,
        'expires_at' => now()->addHour(),
        'joined_at' => now(),
    ]);

    $acceptUrl = URL::temporarySignedRoute(
        'api.invitations.accept',
        now()->addHour(),
        ['invitation' => $invitation->id]
    );

    $this->postJson($acceptUrl)->assertStatus(Response::HTTP_PRECONDITION_FAILED);
});

test('accept returns precondition failed when invitation is expired', function () {
    $inviter = User::factory()->create();
    $organization = $inviter->organizations()->first();
    $invitation = $organization->invitations()->create([
        'inviter_id' => $inviter->id,
        'name' => 'Expired Member',
        'email' => 'expired@example.com',
        'role' => Organization::ROLE_USER,
        'expires_at' => now()->subMinute(),
        'joined_at' => null,
    ]);

    $acceptUrl = URL::temporarySignedRoute(
        'api.invitations.accept',
        now()->addHour(),
        ['invitation' => $invitation->id]
    );

    $this->postJson($acceptUrl)->assertStatus(Response::HTTP_PRECONDITION_FAILED);
});

test('accept returns precondition failed when invitation organization does not exist', function () {
    $inviter = User::factory()->create();
    $organization = $inviter->organizations()->first();
    $invitation = $organization->invitations()->create([
        'inviter_id' => $inviter->id,
        'name' => 'Missing Org Member',
        'email' => 'missing-org@example.com',
        'role' => Organization::ROLE_USER,
        'expires_at' => now()->addHour(),
        'joined_at' => null,
    ]);

    $organization->delete();

    $acceptUrl = URL::temporarySignedRoute(
        'api.invitations.accept',
        now()->addHour(),
        ['invitation' => $invitation->id]
    );

    $this->postJson($acceptUrl)->assertStatus(Response::HTTP_PRECONDITION_FAILED);
});

test('accept returns precondition failed when invitation inviter does not exist', function () {
    $inviter = User::factory()->create();
    $organization = $inviter->organizations()->first();
    $invitation = $organization->invitations()->create([
        'inviter_id' => $inviter->id,
        'name' => 'Missing Inviter Member',
        'email' => 'missing-inviter@example.com',
        'role' => Organization::ROLE_USER,
        'expires_at' => now()->addHour(),
        'joined_at' => null,
    ]);

    $inviter->delete();

    $acceptUrl = URL::temporarySignedRoute(
        'api.invitations.accept',
        now()->addHour(),
        ['invitation' => $invitation->id]
    );

    $this->postJson($acceptUrl)->assertStatus(Response::HTTP_PRECONDITION_FAILED);
});

test('accept marks invitation as joined and returns invitation payload', function () {
    Carbon::setTestNow(now());

    $inviter = User::factory()->create();
    $organization = $inviter->organizations()->first();
    $invitation = $organization->invitations()->create([
        'inviter_id' => $inviter->id,
        'name' => 'Accepted Member',
        'email' => 'accepted@example.com',
        'role' => Organization::ROLE_ADMIN,
        'expires_at' => now()->addHour(),
        'joined_at' => null,
    ]);

    $acceptUrl = URL::temporarySignedRoute(
        'api.invitations.accept',
        now()->addHour(),
        ['invitation' => $invitation->id]
    );

    $oauthClient = $this->configurePersonalGrantType();

    $jsonResponse = $this->postJson($acceptUrl)
        ->assertStatus(Response::HTTP_OK)
        ->json('data');

    expect($jsonResponse['invited_organization_id'])->toBe($organization->id);
    expect($jsonResponse['user']['name'])->toBe('Accepted Member');
    expect($jsonResponse['user']['email'])->toBe('accepted@example.com');
    expect($jsonResponse['user']['created_at'])->toBe(now()->toIso8601String());
    expect($jsonResponse['user']['updated_at'])->toBe(now()->toIso8601String());
    expect($jsonResponse['token_type'])->toBe('Bearer');
    expect(abs($jsonResponse['expires_in'] - now()->subYear()->diffInSeconds()))
        ->toBeLessThanOrEqual(1);

    expect($invitation->fresh()->joined_at->toIso8601String())
        ->toBe(now()->toIso8601String());

    $this->assertDatabaseHas('oauth_access_tokens', [
        'name' => 'From Invitation',
        'user_id' => $jsonResponse['user']['id'],
        'client_id' => $oauthClient->id,
    ]);
});
