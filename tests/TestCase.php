<?php

namespace Tests;

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            Authenticate::class,
            EnsureEmailIsVerified::class
        ]);
    }

    public function logIn(?User $user = null): User
    {
        $user = $user ?? User::factory()->create();
        Passport::actingAs($user);
        return $user;
    }

    protected function mockRefreshAppsRequest() : void
    {
        Http::fake([
            config('ressonance.refresh_reverb_url')
                => Http::response('{"status":"Applications Refreshed"}', 200),
        ]);
    }
}
