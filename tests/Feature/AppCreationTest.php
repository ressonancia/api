<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

test('user can create an app', function () {
    $uuid = Str::uuid();
    $key = Str::random(20);
    $secret = Str::random(20);

    $this->mock(Str::class, function ($mock) use ($uuid, $key, $secret) {
        $mock->makePartial();

        $mock->shouldReceive('uuid')
            ->once()
            ->andReturn($uuid);

        $mock->shouldReceive('random')
            ->once()
            ->andReturn($key);

        $mock->shouldReceive('random')
            ->once()
            ->andReturn($secret);
    });

    $response = $this->postJson(route('api.apps.store'), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP'
    ]);
    $response->assertStatus(Response::HTTP_CREATED);

    $this->assertDatabaseHas('apps', [
        'app_id' => Str::lower($uuid),
        'app_key' => Str::lower($key),
        'app_secret' => Str::lower($secret),
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP',
    ]);
});


test('user needs to be logged in to create app', function () {
    $this->withMiddleware(Authenticate::class);

    $this->postJson(route('api.apps.store'), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP'
    ])->assertUnauthorized();
});


test('user needs to to verify email to create app', function () {
    $this->withMiddleware(EnsureEmailIsVerified::class);

    $this->postJson(route('api.apps.store'), [
        'app_name' => 'Batocera Cloud',
        'app_language_choice' => 'PHP'
    ])->assertForbidden();
});