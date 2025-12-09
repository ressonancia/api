<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config as FacadesConfig;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('can install ressonance', function () {

    Carbon::setTestNow(now());

    FacadesConfig::set('reverb.servers.reverb.port', 8081);

    $this->artisan('ressonance:install')
        ->expectsOutput('User: admin@ressonance.com')
        ->expectsOutput('Access ressonance at port 80.')
        ->expectsOutput('Access ressonance api port 8000.')
        ->expectsOutput('Ressonance websockets API is running at port 8081.')
        ->assertExitCode(0);

    $this->assertDatabaseHas('oauth_clients', [
        'name' => 'First Party SPA',
        'secret' => 'Dq9p296oaZtbaH7HX8v9gD1nuHaWmLSlox8a9Bfk',
    ]);

    $this->assertDatabaseHas('oauth_clients', [
        'name' => 'Ressonance Personal Access Client',
        'secret' => 'qhTkBLYHfqtWRptHfHadOBs3cKM1jmZIkchqSKI2',
    ]);

    $this->assertDatabaseHas(User::class, [
        'name' => 'Admin User',
        'email' => 'admin@ressonance.com',
        'email_verified_at' => now(),
    ]);

    $this->artisan('ressonance:install')
        ->doesntExpectOutput('User: admin@ressonance.com')
        ->expectsOutput('Access ressonance at port 80.')
        ->expectsOutput('Access ressonance api port 8000.')
        ->expectsOutput('Ressonance websockets API is running at port 8081.')
        ->assertExitCode(0);

});
