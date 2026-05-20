<?php

use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

pest()->extend(Tests\TestCase::class);

it('has a default date format', function () {

    Carbon::setTestNow();

    $user = User::factory()->make([
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($user->toArray()['created_at'])
        ->toBe(now()->toIso8601String());

    expect($user->toArray()['updated_at'])
        ->toBe(now()->toIso8601String());
});

it('has an avatar getter', function () {
    $user = User::factory()->make();
    expect($user->avatar)->toBe('https://www.gravatar.com/avatar/'
            .hash('sha256', strtolower(trim($user->email))).'?s=40');
});

it('has many organizations', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $user->organizations()->attach($organization->id, ['role' => Organization::ROLE_ADMIN]);

    $userOrganizations = $user->organizations;

    expect($userOrganizations->pluck('id'))
        ->toContain($organization->id);

    expect($user->organizations())
        ->toBeInstanceOf(BelongsToMany::class);
});

it('creates a default owned organization when the user is created', function () {
    $user = User::factory()->create();

    $ownedOrganization = $user->organizations()
        ->wherePivot('role', Organization::ROLE_OWNER)
        ->first();

    expect($ownedOrganization)->not->toBeNull();
});
