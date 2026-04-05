<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;

class SocialLoginController extends Controller
{
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)
            ->stateless()->redirect();
    }

    public function callback(string $provider)
    {
        $user = Socialite::driver($provider)
            ->stateless()->user();

        $user = User::firstOrCreate(['email' => $user->email], [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => now(),
        ]);

        // Not necesary to trigger Register event
        // Because the email is already validated by the provider
        $token = $user->createToken('From Social Login');

        $authorizationCode = Str::uuid()->toString();

        cache()->put(
            'social_login_'.$authorizationCode,
            [
                'user' => $user,
                'access_token' => $token->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $token->token
                    ->expires_at->diffInSeconds(now()),
            ],
            now()->addMinutes(5)
        );

        return redirect(
            config('ressonance.spa_url')
                .'/social-login?authorizationCode='.$authorizationCode
        );
    }

    public function getAccessToken(string $authorizationCode): JsonResponse
    {
        $socialLoginData = cache()->get('social_login_'.$authorizationCode);

        if (! $socialLoginData) {
            abort(400, 'Invalid authorization code');
        }

        return response()->json($socialLoginData);
    }
}
