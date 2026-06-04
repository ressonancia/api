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

test('organization owner can invite a member by name and email', function () {
    Carbon::setTestNow(now());
    Notification::fake();

    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $response = $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'New Member',
        'email' => 'new-member@example.com',
        'role' => Organization::ROLE_MEMBER,
    ]);

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('organization_id', $organization->id)
        ->assertJsonPath('inviter_id', $owner->id)
        ->assertJsonPath('name', 'New Member')
        ->assertJsonPath('email', 'new-member@example.com')
        ->assertJsonPath('role', Organization::ROLE_MEMBER);

    $invitationId = $response->json('id');

    $this->assertDatabaseHas('invitations', [
        'id' => $invitationId,
        'organization_id' => $organization->id,
        'inviter_id' => $owner->id,
        'name' => 'New Member',
        'email' => 'new-member@example.com',
        'role' => Organization::ROLE_MEMBER,
        'joined_at' => null,
    ]);

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class, function ($notification, $channels, $notifiable) use ($invitationId) {
        expect($notifiable->routes['mail'])->toBe('new-member@example.com');
        expect($notification->invitation->id)->toBe($invitationId);
        expect($notification->invitation->name)->toBe('New Member');
        expect($notification->invitation->role)->toBe(Organization::ROLE_MEMBER);
        expect($notification->invitationUrl)->toStartWith(config('app.spa_url').'/email-invitation?');

        $spaQuery = [];
        parse_str(parse_url($notification->invitationUrl, PHP_URL_QUERY), $spaQuery);
        expect($spaQuery)->toHaveKey('route');

        $encodedRoute = strtr($spaQuery['route'], '-_', '+/');
        $signedRoute = base64_decode($encodedRoute, true);
        $parsedUrl = parse_url($signedRoute);
        expect($parsedUrl['scheme'].'://'.$parsedUrl['host'].$parsedUrl['path'])->toBe(
            route('api.invitations.accept', [
                'invitation' => $notification->invitation->id,
            ])
        );
        $routeQuery = [];
        parse_str($parsedUrl['query'], $routeQuery);
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

test('organization member role cannot invite members', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $this->logIn($user);

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'name' => 'Forbidden User',
        'email' => 'forbidden@example.com',
        'role' => Organization::ROLE_MEMBER,
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
        'role' => Organization::ROLE_MEMBER,
    ])->assertUnauthorized();
});

test('email, name and role are required to invite a member', function () {
    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $this->postJson(route('api.organizations.invitations.store', [
        'organization' => $organization->id,
    ]), [
        'role' => 'Not allowed role',
    ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email', 'name', 'role'])
        ->assertJsonFragment([
            'email' => ['The email field is required.'],
        ])->assertJsonFragment([
            'name' => ['The name field is required.'],
        ])->assertJsonFragment([
            'role' => ['The selected role is invalid.'],
        ]);
});

test('removes previous invitations for the same organization and user before creating a new one', function () {
    Notification::fake();

    $owner = $this->login();
    $organization = $owner->organizations()->first();
    Organization::factory()->create();

    $expiredInvitation = $organization->invitations()->create([
        'inviter_id' => $owner->id,
        'name' => 'Expired Member',
        'email' => 'new-member@example.com',
        'role' => Organization::ROLE_MEMBER,
        'expires_at' => now()->addHour(),
        'joined_at' => null,
    ]);

    $unrelatedInvitation = $organization->invitations()->create([
        'inviter_id' => $owner->id + 1,
        'name' => 'Another unrelated invitation',
        'email' => 'unrelated@example.com',
        'role' => Organization::ROLE_MEMBER,
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
    $this->assertDatabaseHas(Invitation::class, [
        'id' => $unrelatedInvitation->id,
        'email' => 'unrelated@example.com',
        'role' => Organization::ROLE_MEMBER,
    ]);

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
        'role' => Organization::ROLE_MEMBER,
    ]);
});

test('accept requires a signed route', function () {
    $inviter = User::factory()->create();
    $organization = $inviter->organizations()->first();
    $invitation = $organization->invitations()->create([
        'inviter_id' => $inviter->id,
        'name' => 'Unsigned Member',
        'email' => 'unsigned@example.com',
        'role' => Organization::ROLE_MEMBER,
        'expires_at' => now()->addHour(),
        'joined_at' => null,
    ]);

    $this->postJson(route('api.invitations.accept', [
        'invitation' => $invitation->id,
    ]))->assertForbidden();
});

test('accept returns precondition failed when invitation was already joined', function () {
    $inviter = User::factory()->create();
    $organization = $inviter->organizations()->first();
    $invitation = $organization->invitations()->create([
        'inviter_id' => $inviter->id,
        'name' => 'Joined Member',
        'email' => 'joined@example.com',
        'role' => Organization::ROLE_MEMBER,
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
        'role' => Organization::ROLE_MEMBER,
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
        'role' => Organization::ROLE_MEMBER,
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
        'role' => Organization::ROLE_MEMBER,
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

    $unrelatedInvitation = Invitation::factory()->create();

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

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $invitation->organization_id,
        'user_id' => $jsonResponse['user']['id'],
        'role' => $invitation->role,
    ]);

    $this->assertDatabaseHas(Invitation::class, [
        'id' => $unrelatedInvitation->id,
        'email' => $unrelatedInvitation->email,
        'role' => $unrelatedInvitation->role,
        'joined_at' => null,
    ]);
});

test('accept does not break existing users', function () {
    Carbon::setTestNow(now());

    $inviter = User::factory()->create();

    $user = User::factory()->create();

    $anotherUserOrganization = Organization::factory()->create();
    $user->organizations()->attach([
        $anotherUserOrganization->id => ['role' => Organization::ROLE_MEMBER],
    ]);

    $oldInvitation = Invitation::factory([
        'organization_id' => $anotherUserOrganization->id,
        'email' => $user->email,
        'joined_at' => now()->subYear(),
    ])->create();

    $invitedOrganization = Organization::factory()->create();

    $newInvitation = Invitation::factory([
        'inviter_id' => $inviter->id,
        'organization_id' => $invitedOrganization->id,
        'email' => $user->email,
        'name' => 'User Name From New Invitation',
        'role' => Organization::ROLE_ADMIN,
        'expires_at' => now()->addHour(),
    ])->create();

    $acceptUrl = URL::temporarySignedRoute(
        'api.invitations.accept',
        now()->addHour(),
        ['invitation' => $newInvitation->id]
    );

    $this->configurePersonalGrantType();

    $this->postJson($acceptUrl)
        ->assertStatus(Response::HTTP_OK);

    $this->assertDatabaseHas(Invitation::class, [
        'id' => $newInvitation->id,
        'email' => $newInvitation->email,
        'role' => $newInvitation->role,
        'joined_at' => now(),
    ]);

    $this->assertDatabaseHas(Invitation::class, [
        'id' => $oldInvitation->id,
        'email' => $oldInvitation->email,
        'role' => $oldInvitation->role,
        'joined_at' => now()->subYear(),
    ]);

    $allUserOrganizations = User::findOrFail($user->id)->organizations;

    expect($allUserOrganizations->count())->toBe(3);
    expect($allUserOrganizations->contains($invitedOrganization))->toBeTrue();
    expect($allUserOrganizations->contains($anotherUserOrganization))->toBeTrue();
});
