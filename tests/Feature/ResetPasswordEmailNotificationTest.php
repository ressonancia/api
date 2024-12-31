<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;

test('user can receive a reset password notificatoin', function () {
    $user = User::factory()->create();

    Notification::fake();

    $response = $this->postJson(
        route('api.password.email'),
        ['email' => $user->email]
    );

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson([
            'message' => 'Reset password sent',
        ]);

    Notification::assertSentTo($user, ResetPassword::class);

    expect(
        call_user_func(ResetPassword::$createUrlCallback, $user, 'fake-token')
    )->tobe(config('app.spa_url') . '/reset-password?token=fake-token');
});

test('user needs a valid emal', function () {
    $response = $this->postJson(
        route('api.password.email')
    );
    
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
    ->assertJsonValidationErrors(['email'])
    ->assertJsonFragment([
        'email' => ['The email field is required.'],
    ]);;

    $response = $this->postJson(
        route('api.password.email'),
        ['email' => 'invalid-email']
    );

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['email'])
        ->assertJsonFragment([
            'email' => ['The email field must be a valid email address.'],
        ]);;
});