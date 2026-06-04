<?php

use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;

pest()->extend(Tests\TestCase::class);

it('allows viewing organization for members', function () {
    $policy = new OrganizationPolicy;
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    expect($policy->view($user, $organization))->toBeTrue();
});

it('denies viewing organization for non members', function () {
    $policy = new OrganizationPolicy;
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    expect($policy->view($user, $organization))->toBeFalse();
});

it('allows editing organization for owners', function () {
    $policy = new OrganizationPolicy;
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_OWNER,
    ]);

    expect($policy->edit($user, $organization))->toBeTrue();
});

it('allows editing organization for admins', function () {
    $policy = new OrganizationPolicy;
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_ADMIN,
    ]);

    expect($policy->edit($user, $organization))->toBeTrue();
});

it('denies editing organization for members', function () {
    $policy = new OrganizationPolicy;
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    expect($policy->edit($user, $organization))->toBeFalse();
});

it('denies editing organization for non members', function () {
    $policy = new OrganizationPolicy;
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    expect($policy->edit($user, $organization))->toBeFalse();
});
