<?php

use App\Jobs\RefreshReverb;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

test('user can create an app', function () {
    Queue::fake();

    $user = $this->login();
    $organization = $user->organizations()->first();

    $key = Str::random(20);
    $secret = Str::random(20);

    $this->mock(Str::class, function ($mock) use ($key, $secret) {
        $mock->makePartial();

        $mock->shouldReceive('random')
            ->once()
            ->andReturn($key);

        $mock->shouldReceive('random')
            ->once()
            ->andReturn($secret);
    });

    $response = $this->postJson(route('api.apps.store', ['organization' => $organization->id]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ]);
    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('apps', [
        'organization_id' => $organization->id,
        'app_key' => Str::lower($key),
        'app_secret' => Str::lower($secret),
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ]);

    Queue::assertPushed(RefreshReverb::class);
});

test('user cannot create app for organization they do not belong to', function () {
    $this->login();
    $organization = Organization::factory()->create();

    $this->postJson(route('api.apps.store', ['organization' => $organization->id]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ])->assertForbidden();
});

test('organization member with member role cannot create an app', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $this->logIn($user);

    $this->postJson(route('api.apps.store', ['organization' => $organization->id]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ])->assertForbidden();
});

test('organization member with admin role can create an app', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_ADMIN,
    ]);

    $this->logIn($user);

    $response = $this->postJson(route('api.apps.store', ['organization' => $organization->id]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ]);

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('apps', [
        'organization_id' => $organization->id,
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ]);

    Queue::assertPushed(RefreshReverb::class);
});

test('organization member with owner role can create an app', function () {
    Queue::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_OWNER,
    ]);

    $this->logIn($user);

    $response = $this->postJson(route('api.apps.store', ['organization' => $organization->id]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ]);

    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('apps', [
        'organization_id' => $organization->id,
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ]);

    Queue::assertPushed(RefreshReverb::class);
});

test('user needs to be logged in to create app', function () {
    $this->withMiddleware(Authenticate::class);

    $this->postJson(route('api.apps.store', ['organization' => Str::uuid()->toString()]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ])->assertUnauthorized();
});

test('user needs to to verify email to create app', function () {
    $this->withMiddleware(EnsureEmailIsVerified::class);
    $organization = Organization::factory()->create();

    $this->postJson(route('api.apps.store', ['organization' => $organization->id]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ])->assertForbidden();
});
