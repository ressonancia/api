<?php

use App\Models\App;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

test('owner can delete organization and all organization apps are deleted', function () {
    $user = $this->login();

    $organization = $user->organizations()->first();

    $preconditionOrganization = Organization::factory()->create();
    $user->organizations()->attach($preconditionOrganization->id, [
        'role' => Organization::ROLE_OWNER,
    ]);

    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Organization deleted', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

    $response = $this->deleteJson(route('api.organizations.destroy', [
        'organization' => $organization->id,
    ]));

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing(Organization::class, [
        'id' => $organization->id,
    ]);

    $this->assertSoftDeleted(App::class, [
        'id' => $app->id,
        'organization_id' => $organization->id,
    ]);

    $this->assertDatabaseMissing(OrganizationUser::class, [
        'organization_id' => $organization->id,
    ]);
});

test('user cannot delete organization if user is not owner', function () {
    $owner = User::factory()->create();
    $organization = Organization::factory()->create();

    $organization->users()->attach($owner->id, [
        'role' => Organization::ROLE_OWNER,
    ]);

    $user = $this->login();

    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_ADMIN,
    ]);

    $this->deleteJson(route('api.organizations.destroy', [
        'organization' => $organization->id,
    ]))->assertNotFound();

    $this->assertDatabaseHas(Organization::class, [
        'id' => $organization->id,
    ]);
});

test('user cannot delete last owned organization', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $response = $this->deleteJson(route('api.organizations.destroy', [
        'organization' => $organization->id,
    ]));

    $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);
    $response->assertJsonPath('message', 'The user cannot delete the last owned organization');

    $this->assertDatabaseHas(Organization::class, [
        'id' => $organization->id,
    ]);
});

test('user needs to be logged in to delete organization', function () {
    $organization = Organization::factory()->create();

    $this->withMiddleware(Authenticate::class);

    $this->deleteJson(route('api.organizations.destroy', [
        'organization' => $organization->id,
    ]))->assertUnauthorized();
});
