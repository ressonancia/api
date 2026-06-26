<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

test('organization member can view organization with unpaginated users and pivot information', function () {
    $owner = $this->login();
    $organization = $owner->organizations()->first();

    $member = User::factory()->create();
    $organization->users()->attach($member->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $organization->refresh()->load('users');

    $response = $this->getJson(route('api.organizations.show', [
        'organization' => $organization->id,
    ]));

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('id', $organization->id)
        ->assertJsonPath('name', $organization->name)
        ->assertJsonCount(2, 'users')
        ->assertJsonMissingPath('users.data')
        ->assertJsonFragment([
            'id' => $owner->id,
            'email' => $owner->email,
            'role' => Organization::ROLE_OWNER,
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
        ])
        ->assertJsonFragment([
            'id' => $member->id,
            'email' => $member->email,
            'role' => Organization::ROLE_MEMBER,
            'organization_id' => $organization->id,
            'user_id' => $member->id,
        ]);
});

test('user cannot view another organization', function () {
    $this->login();

    $organization = Organization::factory()->create();

    $this->getJson(route('api.organizations.show', [
        'organization' => $organization->id,
    ]))->assertForbidden();
});

test('user receives a 404 when querying for a non existent organization', function () {
    $organizationId = Str::uuid()->toString();

    $response = $this->getJson(route('api.organizations.show', [
        'organization' => $organizationId,
    ]));

    $response->assertStatus(Response::HTTP_NOT_FOUND)
        ->assertJson([
            'message' => "No query results for model [App\Models\Organization] {$organizationId}",
        ]);
});

test('user needs to be logged in to show organization', function () {
    $this->withMiddleware(Authenticate::class);

    $this->getJson(route('api.organizations.show', [
        'organization' => Str::uuid()->toString(),
    ]))->assertUnauthorized();
});
