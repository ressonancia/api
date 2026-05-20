<?php

use App\Models\Organization;
use Illuminate\Support\Str;

pest()->extend(Tests\TestCase::class);

it('sets uuid automatically when creating an organization', function () {
    $organization = Organization::factory()->create();

    expect($organization->id)->not->toBeEmpty();
    expect(Str::isUuid($organization->id))->toBeTrue();
});
