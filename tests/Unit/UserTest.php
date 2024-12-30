<?php

use App\Models\User;
use Carbon\Carbon;

pest()->extend(Tests\TestCase::class);

test('user model has a default date format', function () {

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
