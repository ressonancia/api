<?php

use Illuminate\Auth\Middleware\Authenticate;

test('user can retrieve its own information with organizations', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();
    $membership = $user->organizations()->first()->pivot;

    $this->getJson(route('api.me'))
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('organizations.0.id', $organization->id)
        ->assertJsonPath('organizations.0.name', $organization->name)
        ->assertJsonPath('organizations.0.pivot.id', $membership->id)
        ->assertJsonPath('organizations.0.pivot.user_id', $user->id)
        ->assertJsonPath('organizations.0.pivot.organization_id', $organization->id)
        ->assertJsonPath('organizations.0.pivot.role', $membership->role)
        ->assertJsonStructure([
            'organizations' => [[
                'id',
                'name',
                'created_at',
                'updated_at',
                'pivot' => [
                    'id',
                    'user_id',
                    'organization_id',
                    'role',
                    'created_at',
                    'updated_at',
                ],
            ]],
        ]);
});

test('user needs to be logged in to retrieve its own information', function () {
    $this->withMiddleware(Authenticate::class);

    $this->getJson(route('api.me'))->assertUnauthorized();
});
