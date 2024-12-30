<?php

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('user can send another email verify notification', function () {

    Notification::fake();

    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::signedRoute('verification.verify', [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('verification.send'));

    $response->assertStatus(Response::HTTP_OK);
    $response->assertJson([
        'message' => 'Verification link sent!'
    ]);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('user needs to be authenticated to send another email', function () {
    $this->withMiddleware([
        Authenticate::class,
    ]);
    
    $response = $this->postJson(
        route('verification.send')
    )->assertUnauthorized();
});