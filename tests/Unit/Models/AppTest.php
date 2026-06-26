<?php

use App\Models\App;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

pest()->extend(Tests\TestCase::class);

it('uses soft deletes', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(App::class), true))->toBeTrue();
});

it('hides deleted_at in serialized output', function () {
    $organization = Organization::factory()->create();
    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $serialized = $app->toArray();

    expect($serialized)->not->toHaveKey('deleted_at');
});

it('belongs to an organization relation', function () {
    $organization = Organization::factory()->create();
    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($app->organization()->getRelated()::class)->toBe(Organization::class);
    expect($app->organization())->toBeInstanceOf(BelongsTo::class);
    expect($app->organization->id)->toBe($organization->id);
});
