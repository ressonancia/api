<?php

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

test('user can change its password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('milho'),
    ]);

    $token = Password::createToken($user);

    Log::shouldReceive('info')
        ->once()
        ->with('Password Reseted For: ' . $user->email);
    
    $response = $this->postJson(route('password.reset'), [
        'email' => $user->email,
        'token' => $token,
        'password' => 'Pipoka123!',
        'password_confirmation' => 'Pipoka123!',
    ]);

    $response->assertStatus(Response::HTTP_OK)->assertJson([
        'message' => 'Password has changed'
    ]);

    $this->assertTrue(Hash::check('Pipoka123!', $user->fresh()->password));
});

test('user can not reset password with invalid token', function () {
    $user = User::factory()->create([
        'password' => Hash::make('milho'),
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Password Reset Try With Invalid Token For: ' . $user->email);
    
    $response = $this->postJson(route('password.reset'), [
        'email' => $user->email,
        'token' => 'invelid-token',
        'password' => 'Pipoka123!',
        'password_confirmation' => 'Pipoka123!',
    ]);

    $response->assertStatus(Response::HTTP_BAD_REQUEST)->assertJson([
        'message' => 'Invalid token'
    ]);

    $this->assertTrue(Hash::check('milho', $user->fresh()->password));
})->only();

test('user needs to give a valid token', function () {
    $this->postJson(route('password.reset'), [])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['token'])
        ->assertJsonFragment([
            'token' => ['The token field is required.'],
        ]);
});

test('user needs to give a valid email', function () {

    $this->postJson(route('password.reset'))->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email'])
        ->assertJsonFragment([
            'email' => ['The email field is required.'],
        ]);

    $this->postJson(route('password.reset'), [
        'email' => 'invalid'
    ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email'])
        ->assertJsonFragment([
            'email' => ['The email field must be a valid email address.'],
        ]);
});

test('user needs to give a valid password', function () {

    $this->postJson(route('password.reset'), [
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