<?php

use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

test('user can verify an email address', function () {
    // Arrange: Create a user with an unverified email
    $user = User::factory()->unverified()->create();

    // Act: Simulate a signed URL for email verification
    $verificationUrl = URL::signedRoute('verification.verify', [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $response = $this->actingAs($user)->postJson($verificationUrl);

    // Assert: Check the response and that the email is verified
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJson([
        'data' => [
            'verified' => true,
        ],
    ]);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('user needs to be authenticated to verify email', function () {
    $response = $this->postJson(
        route('verification.verify', [1, uniqid()])
    )->assertForbidden();
});
