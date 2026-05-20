<?php

use App\Models\App;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

pest()->extend(Tests\TestCase::class);

it('sets uuid automatically when creating an organization', function () {
    $organization = Organization::factory()->create();

    expect($organization->id)->not->toBeEmpty();
    expect(Str::isUuid($organization->id))->toBeTrue();
});

it('has many apps relation', function () {
    $organization = Organization::factory()->create();
    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($organization->apps()->getRelated()::class)->toBe(App::class);
    expect($organization->apps())->toBeInstanceOf(HasMany::class);
    expect($organization->apps->pluck('id'))->toContain($app->id);
});

it('belongs to many users relation', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user->id, [
        'id' => (string) Str::uuid(),
        'role' => Organization::ROLE_ADMIN,
    ]);

    expect($organization->users()->getRelated()::class)->toBe(User::class);
    expect($organization->users())->toBeInstanceOf(BelongsToMany::class);
    expect($organization->users->pluck('id'))->toContain($user->id);
});
