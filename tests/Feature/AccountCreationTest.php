<?php

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Token;
use SendKit\Laravel\Facades\SendKit;

test('user can create an account', function () {

    Carbon::setTestNow();
    Event::fake(Registered::class);

    $oauthClient = $this->configurePersonalGrantType();

    $response = $this->postJson(route('api.account.store'), [
        'name' => 'Fabio Lioni',
        'email' => 'lioni@ressonance.com',
        'password' => 'VerySecret123',
        'password_confirmation' => 'VerySecret123',
    ]);

    $jsonResponse = $response->assertStatus(Response::HTTP_CREATED)
        ->json();

    expect($jsonResponse['user']['name'])->toBe('Fabio Lioni');
    expect($jsonResponse['user']['email'])->toBe('lioni@ressonance.com');
    expect($jsonResponse['user']['created_at'])->toBe(now()->toIso8601String());
    expect($jsonResponse['user']['updated_at'])->toBe(now()->toIso8601String());
    expect($jsonResponse['token_type'])->toBe('Bearer');
    expect($jsonResponse['access_token'])->not->toBeEmpty();
    expect($jsonResponse['expires_in'] - abs(now()->addYear()->diffInSeconds()))
        ->toBeLessThanOrEqual(1);

    Event::assertDispatched(Registered::class, function ($eventUser) {
        return $eventUser->user->email === 'lioni@ressonance.com';
    });

    $this->assertDatabaseHas(User::class, [
        'name' => 'Fabio Lioni',
        'email' => 'lioni@ressonance.com',
    ]);

    $this->assertDatabaseHas(Token::class, [
        'name' => 'Pending Validation',
        'user_id' => $jsonResponse['user']['id'],
        'client_id' => $oauthClient->id,
    ]);

    $this->assertDatabaseHas(OrganizationUser::class, [
        'user_id' => $jsonResponse['user']['id'],
        'role' => 'owner',
    ]);

    $this->assertDatabaseHas(Organization::class, [
        'name' => "Fabio Lioni's Organization",
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
                'The password field must contain at least one number.',
            ],
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
            ],
        ]);
});

test('user email can be blocked by sendkit validation', function () {
    Event::fake(Registered::class);

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

    Log::shouldReceive('info')
        ->once()
        ->with('Email verification failed', [
            'email' => 'blocked@ressonance.com',
            'result' => [
                'should_block' => true,
            ],
        ]);

    $this->postJson(route('api.account.store'), [
        'name' => 'Fabio Lioni',
        'email' => 'blocked@ressonance.com',
        'password' => 'VerySecret123',
        'password_confirmation' => 'VerySecret123',
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email'])
        ->assertJsonFragment([
            'email' => ['The email field must be a valid email address.'],
        ]);

    Event::assertNotDispatched(Registered::class);

    $this->assertDatabaseMissing(User::class, [
        'email' => 'blocked@ressonance.com',
    ]);
});
