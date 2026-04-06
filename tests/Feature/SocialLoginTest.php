<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;

beforeEach(function () {
    Carbon::setTestNow(now());
    Socialite::fake('github');
    $this->configurePersonalGrantType();
    $this->uuid = (string) Str::uuid();
    Str::createUuidsUsing(fn () => $this->uuid);
});

test('user is redirected to github', function () {
    $response = $this->get(
        route('api.social.auth.redirect', ['provider' => 'github'])
    );
    $response->assertRedirect()
        ->assertSee('github');

    $response = $this->get(
        route('api.social.auth.redirect', ['provider' => 'google'])
    );
    $response->assertRedirect()
        ->assertSee('google');
});

test('user can signup with social accounts', function () {
    $user = User::factory()->make();
    Socialite::fake('github', $user);

    $response = $this->getJson(
        route('api.social.auth.callback', ['provider' => 'github'])
    );

    $response->assertRedirect(
        config('ressonance.spa_url')
            .'/social-login?authorizationCode='.$this->uuid
    );

    $this->assertDatabaseHas(User::class, [
        'name' => $user->name,
        'email' => $user->email,
        'email_verified_at' => now(),
    ]);

    $this->assertDatabaseHas('oauth_access_tokens', [
        'name' => 'From Social Login',
        'user_id' => User::where('email', $user->email)->first()->id,
        'client_id' => $this->oauthClient->id,
    ]);

    $cachedSocialLoginData = cache()->get('social_login_'.$this->uuid);

    expect($cachedSocialLoginData)->not->toBeNull();
    expect($cachedSocialLoginData['user']->email)->toBe($user->email);
    expect($cachedSocialLoginData['token_type'])->toBe('Bearer');
    expect($cachedSocialLoginData['access_token'])->not->toBeEmpty();
    expect((int) ceil($cachedSocialLoginData['expires_in']))
        ->toBe((int) ceil(now()->diffInSeconds(now()->addYear(), true)));

});

test('social login does not duplicate users', function () {
    $user = User::factory()->create();
    Socialite::fake('github', $user);

    $response = $this->getJson(
        route('api.social.auth.callback', ['provider' => 'github'])
    );

    $response->assertRedirect(
        config('ressonance.spa_url')
            .'/social-login?authorizationCode='.$this->uuid
    );

    expect(
        cache()->get('social_login_'.$this->uuid)['user']->id,
    )->toBe($user->fresh()->id);
});

test('authorization code expires after 5 minutes', function () {
    $user = User::factory()->create();
    Socialite::fake('github', $user);

    $this->getJson(
        route('api.social.auth.callback', ['provider' => 'github'])
    );

    $this->travel(4)->minutes();
    expect(cache()->has('social_login_'.$this->uuid))->toBeTrue();

    $this->travel(1)->minutes();
    expect(cache()->has('social_login_'.$this->uuid))->toBeFalse();
});

test('can i exchange an authorization code for an access token', function () {
    $user = User::factory()->make();

    cache()->put(
        'social_login_'.$this->uuid,
        [
            'user' => $user,
            'access_token' => uniqid(),
            'token_type' => 'Bearer',
            'expires_in' => 99999,
        ],
        now()->addMinutes(5)
    );

    $response = $this->postJson(
        route('api.social.auth.access-token', ['authorizationCode' => $this->uuid])
    );

    $response->assertOk()
        ->assertJson([
            'user' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
            'access_token' => cache()->get('social_login_'.$this->uuid)['access_token'],
            'token_type' => 'Bearer',
            'expires_in' => 99999,
        ]);

    $this->travel(6)->minutes();

    $response = $this->postJson(
        route('api.social.auth.access-token', ['authorizationCode' => $this->uuid])
    )->assertBadRequest();
});
