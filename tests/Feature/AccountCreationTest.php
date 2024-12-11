<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;

test('user can create an account', function () {

    Carbon::setTestNow();
    Event::fake(Registered::class);

    $response = $this->postJson(route('api.account.store'), [
        'name' => 'Fabio Lioni',
        'email' => 'lioni@ressonance.com',
        'password' => 'VerySecret123',
        'password_confirmation' => 'VerySecret123',
    ]);

    $response->assertStatus(Response::HTTP_CREATED)
        ->assertJson([
            'name' => 'Fabio Lioni',
            'email' => 'lioni@ressonance.com',
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String()
        ]);

    Event::assertDispatched(Registered::class, function ($eventUser) {
        return $eventUser->user->email === 'lioni@ressonance.com';
    });

    $this->assertDatabaseHas('users', [
        'name' => 'Fabio Lioni',
        'email' => 'lioni@ressonance.com'
    ]);
});

test('user needs to give a valid name', function () {
    $this->postJson(route('api.account.store'), [])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['name'])
        ->assertJsonFragment([
            'name' => ['The name field is required.'],
        ]);
});

test('user needs to give a valid email', function () {

    $this->postJson(route('api.account.store'), [
        'name' => 'Fabio Lioni',
        'password' => 'VerySecret123',
        'password_confirmation' => 'VerySecret123',
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email'])
        ->assertJsonFragment([
            'email' => ['The email field is required.'],
        ]);

    $this->postJson(route('api.account.store'), [
        'name' => 'Fabio Lioni',
        'email' => 'invalid',
        'password' => 'VerySecret123',
        'password_confirmation' => 'VerySecret123',
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email'])
        ->assertJsonFragment([
            'email' => ['The email field must be a valid email address.'],
        ]);
});

test('user needs to give a valid password', function () {

    $this->postJson(route('api.account.store'), [
        'name' => 'Fabio Lioni',
        'email' => 'lioni@ressonance.com',
        'password' => 'a',
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['password'])
        ->assertJsonFragment([
            'password' => [
                'The password field confirmation does not match.',
                'The password field must be at least 8 characters.',
                'The password field must contain at least one uppercase and one lowercase letter.',
                'The password field must contain at least one number.'
            ]
        ]);
});

test('user email needs to be unique', function () {

    $user = User::factory()->create();

    $this->postJson(route('api.account.store'), [
        'name' => 'Fabio Lioni',
        'email' => $user->email,
        'password' => 'a',
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email'])
        ->assertJsonFragment([
            'email' => [
                'The email has already been taken.',
            ]
        ]);
});