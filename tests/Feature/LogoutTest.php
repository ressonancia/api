<?php

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;

test('user can logout', function () {
    $this->withMiddleware(Authenticate::class);

    $this->configurePersonalGrantType();

    $user = User::factory()->create();
    $token = $user->createToken('Logout Test');

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token->accessToken,
    ])->postJson(route('api.logout'))->assertNoContent();

    expect(($user->tokens->every(fn ($token) => $token->revoked)))->toBeTrue();
});
