<?php

namespace Tests;

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    public Client $oauthClient;

    protected function setUp(): void
    {
        parent::setUp();
        Str::createUuidsNormally();
        $this->withoutMiddleware([
            Authenticate::class,
            EnsureEmailIsVerified::class,
        ]);
    }

    public function configurePersonalGrantType(): Client
    {
        $this->oauthClient = Client::factory()->create([
            'id' => 2,
            'personal_access_client' => 1,
            'secret' => 'qhTkBLYHfqtWRptHfHadOBs3cKM1jmZIkchqSKI2',
        ]);

        return $this->oauthClient;
    }

    public function logIn(?User $user = null): User
    {
        $user = $user ?? User::factory()->create();
        Passport::actingAs($user);

        return $user;
    }

    protected function mockRefreshAppsRequest(): void
    {
        Http::fake([
            config('ressonance.refresh_reverb_url') => Http::response('{"status":"Applications Refreshed"}', 200),
        ]);
    }
}
