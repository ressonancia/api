<?php

use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

pest()->extend(Tests\TestCase::class);

it('belongs to many organizations relation', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->organizations()->attach($organization->id, [
        'id' => (string) Str::uuid(),
        'role' => Organization::ROLE_MEMBER,
    ]);

    expect($user->organizations()->getRelated()::class)->toBe(Organization::class);
    expect($user->organizations())->toBeInstanceOf(BelongsToMany::class);
    expect($user->organizations->pluck('id'))->toContain($organization->id);
});

it('has a default date format', function () {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $user = User::factory()->make([
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    expect($user->toArray()['created_at'])
        ->toBe($now->toIso8601String());

    expect($user->toArray()['updated_at'])
        ->toBe($now->toIso8601String());
});

it('has an avatar getter', function () {
    $user = User::factory()->make();
    expect($user->avatar)->toBe('https://www.gravatar.com/avatar/'
            .hash('sha256', strtolower(trim($user->email))).'?s=40');
});

it('has many organizations', function () {
    $user = User::factory()->create();

    expect($user->organizations())
        ->toBeInstanceOf(BelongsToMany::class);
});

it('creates an owner organization when user is created', function () {
    $user = User::factory()->create([
        'name' => 'Linoni',
    ]);

    $ownerOrganization = $user->organizations()
        ->wherePivot('role', Organization::ROLE_OWNER)
        ->first();

    expect($ownerOrganization)->not->toBeNull();
    expect($user->organizations)->toHaveCount(1);
    expect($ownerOrganization->name)->toBe("Linoni's Organization");
});

it('can skip automatic organization creation once', function () {
    $userWithoutOrganization = User::withoutOrganizationCreation()->factory()->create([
        'name' => 'Linoni',
    ]);

    $userWithOrganization = User::factory()->create([
        'name' => 'Zaphod',
    ]);

    $ownerOrganization = $userWithOrganization->organizations()
        ->wherePivot('role', Organization::ROLE_OWNER)
        ->first();

    expect($userWithoutOrganization->organizations)->toHaveCount(0);
    expect(Organization::query()->where('name', "Linoni's Organization")->doesntExist())
        ->toBeTrue();
    expect($ownerOrganization)->not->toBeNull();
    expect($userWithOrganization->organizations)->toHaveCount(1);
    expect($ownerOrganization->name)->toBe("Zaphod's Organization");
});
