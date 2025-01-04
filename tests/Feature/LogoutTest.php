<?php

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Laravel\Passport\Client;

test('user can logout', function () {
    $this->withMiddleware(Authenticate::class);

    Client::factory()->create([
        'id' => 2,
        'personal_access_client' => 1,
        'secret' => 'qhTkBLYHfqtWRptHfHadOBs3cKM1jmZIkchqSKI2'
    ]);

    $user = User::factory()->create();
    $token = $user->createToken('Logout Test');

    $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->accessToken
    ])->postJson(route('api.logout'))->assertNoContent();

    expect(($user->tokens->every(fn($token) => $token->revoked)))->toBeTrue();
})->only();