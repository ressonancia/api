<?php

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

pest()->extend(Tests\TestCase::class);

it('sets uuid automatically when creating an invitation', function () {
    $invitation = Invitation::factory()->create([
        'email' => 'invitee@example.com',
    ]);

    expect($invitation->id)->not->toBeEmpty();
    expect(Str::isUuid($invitation->id))->toBeTrue();
});

it('keeps custom invitation id when provided', function () {
    $customId = (string) Str::uuid();

    $invitation = Invitation::factory()->make([
        'email' => 'invitee@example.com',
    ]);
    $invitation->id = $customId;
    $invitation->save();

    expect($invitation->id)->toBe($customId);
});

it('casts expires_at and joined_at to datetime', function () {
    $invitation = Invitation::factory()->create([
        'email' => 'invitee@example.com',
        'expires_at' => now()->addDay()->toDateTimeString(),
        'joined_at' => now()->toDateTimeString(),
    ]);

    expect($invitation->expires_at)->toBeInstanceOf(Carbon::class);
    expect($invitation->joined_at)->toBeInstanceOf(Carbon::class);
});

it('belongs to an organization', function () {
    $organization = Organization::factory()->create();

    $invitation = Invitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'invitee@example.com',
    ]);

    expect($invitation->organization()->getRelated()::class)->toBe(Organization::class);
    expect($invitation->organization())->toBeInstanceOf(BelongsTo::class);
    expect($invitation->organization->id)->toBe($organization->id);
});

it('belongs to an inviter user', function () {
    $inviter = User::factory()->create();

    $invitation = Invitation::factory()->create([
        'inviter_id' => $inviter->id,
        'email' => 'invitee@example.com',
    ]);

    expect($invitation->inviter()->getRelated()::class)->toBe(User::class);
    expect($invitation->inviter())->toBeInstanceOf(BelongsTo::class);
    expect($invitation->inviter->id)->toBe($inviter->id);
});
