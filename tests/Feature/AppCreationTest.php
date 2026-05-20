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

    $response = $this->postJson(route('api.apps.store', [
        'organization' => $organization->id,
    ]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ]);
    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('apps', [
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'app_key' => Str::lower($key),
        'app_secret' => Str::lower($secret),
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ]);

    Queue::assertPushed(RefreshReverb::class);
});

test('user needs to be logged in to create app', function () {
    $this->withMiddleware(Authenticate::class);
    $organization = Organization::factory()->create();

    $this->postJson(route('api.apps.store', [
        'organization' => $organization->id,
    ]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ])->assertUnauthorized();
});

test('user needs to to verify email to create app', function () {
    $this->withMiddleware(EnsureEmailIsVerified::class);
    $organization = Organization::factory()->create();

    $this->postJson(route('api.apps.store', [
        'organization' => $organization->id,
    ]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ])->assertForbidden();
});

test('members cannot create apps', function () {
    $user = $this->login();
    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();
    $organization->users()->attach($user->id, ['role' => Organization::ROLE_MEMBER]);

    $this->postJson(route('api.apps.store', [
        'organization' => $organization->id,
    ]), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ])->assertForbidden();
});
