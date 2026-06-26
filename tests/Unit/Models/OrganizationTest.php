<?php

use App\Models\App;
use App\Models\Invitation;
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
        'role' => Organization::ROLE_ADMIN,
    ]);

    expect($organization->users()->getRelated()::class)->toBe(User::class);
    expect($organization->users())->toBeInstanceOf(BelongsToMany::class);
    expect($organization->users->pluck('id'))->toContain($user->id);
});

it('has many invitations relation', function () {
    $organization = Organization::factory()->create();
    $inviter = User::factory()->create();
    $invitation = Invitation::create([
        'organization_id' => $organization->id,
        'inviter_id' => $inviter->id,
        'name' => 'Invitee Name',
        'email' => 'invitee@example.com',
        'role' => Organization::ROLE_MEMBER,
        'expires_at' => now()->addDay(),
    ]);

    expect($organization->invitations()->getRelated()::class)->toBe(Invitation::class);
    expect($organization->invitations())->toBeInstanceOf(HasMany::class);
    expect($organization->invitations->pluck('id'))->toContain($invitation->id);
});
