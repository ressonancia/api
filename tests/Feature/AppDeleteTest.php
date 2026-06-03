<?php

use App\Jobs\RefreshReverb;
use App\Models\App;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

test('user can delete an app', function () {
    Queue::fake();
    $user = $this->login();
    $organization = $user->organizations()->first();

    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $appToKeep = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $response = $this->deleteJson(route('api.apps.destroy', [
        'organization' => $organization->id,
        'app' => $app->id,
    ]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('apps', [
        'id' => $app->id,
        'deleted_at' => null,
    ]);

    $this->assertDatabaseHas('apps', [
        'id' => $appToKeep->id,
        'deleted_at' => null,
    ]);

    Queue::assertPushed(RefreshReverb::class);
});

test('user cannot delete an app from another organization', function () {
    $user = $this->login();

    $anotherOrganization = Organization::factory()->create();

    $app = App::factory()->create([
        'organization_id' => $anotherOrganization->id,
    ]);

    $this->deleteJson(route('api.apps.destroy', [
        'organization' => $anotherOrganization->id,
        'app' => $app->id,
    ]))
        ->assertForbidden();
});

test('organization member with member role cannot delete an app', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $organization->users()->attach($user->id, [
        'id' => (string) Str::uuid(),
        'role' => Organization::ROLE_MEMBER,
    ]);
    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->logIn($user);

    $this->deleteJson(route('api.apps.destroy', [
        'organization' => $organization->id,
        'app' => $app->id,
    ]))
        ->assertForbidden();
});

test('organization member with admin role can delete an app', function () {
    Queue::fake();
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $organization->users()->attach($user->id, [
        'id' => (string) Str::uuid(),
        'role' => Organization::ROLE_ADMIN,
    ]);
    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->logIn($user);

    $this->deleteJson(route('api.apps.destroy', [
        'organization' => $organization->id,
        'app' => $app->id,
    ]))
        ->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertSoftDeleted('apps', [
        'id' => $app->id,
    ]);

    Queue::assertPushed(RefreshReverb::class);
});

test('organization member with owner role can delete an app', function () {
    Queue::fake();
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $organization->users()->attach($user->id, [
        'id' => (string) Str::uuid(),
        'role' => Organization::ROLE_OWNER,
    ]);
    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->logIn($user);

    $this->deleteJson(route('api.apps.destroy', [
        'organization' => $organization->id,
        'app' => $app->id,
    ]))
        ->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertSoftDeleted('apps', [
        'id' => $app->id,
    ]);

    Queue::assertPushed(RefreshReverb::class);
});

test('user cannot mix organization and app_id', function () {
    $user = $this->login();
    $organizationId = $user->organizations()->first()->id;
    $app = App::factory()->create([
        'organization_id' => $organizationId,
    ]);

    $app = App::factory()->create([
        'organization_id' => Str::uuid()->toString(),
    ]);

    $this->deleteJson(route('api.apps.destroy', [
        'organization' => $organizationId,
        'app' => $app->id,
    ]))
        ->assertNotFound();
});

test('user needs to be logged in to delete an app', function () {
    $this->withMiddleware(Authenticate::class);
    $organization = Organization::factory()->create();

    $this->deleteJson(route('api.apps.destroy', [
        'organization' => $organization->id,
        'app' => 1,
    ]))->assertUnauthorized();
});
