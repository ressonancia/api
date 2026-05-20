<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

pest()->extend(Tests\TestCase::class);

it('belongs to many organizations relation', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->organizations()->attach($organization->id, [
        'id' => (string) Str::uuid(),
        'role' => Organization::ROLE_USER,
    ]);

    expect($user->organizations()->getRelated()::class)->toBe(Organization::class);
    expect($user->organizations())->toBeInstanceOf(BelongsToMany::class);
    expect($user->organizations->pluck('id'))->toContain($organization->id);
});
