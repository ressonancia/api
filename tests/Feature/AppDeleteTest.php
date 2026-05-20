<?php

use App\Jobs\RefreshReverb;
use App\Models\App;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Queue;

test('user can delete an app', function () {
    Queue::fake();
    $user = $this->login();
    $organization = $user->organizations()->first();

    $app = App::factory()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    $appToKeep = App::factory()->create([
        'user_id' => $user->id,
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

test('user cannot delete an app from another user', function () {
    $user = $this->login();
    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();
    $organization->users()->attach($user->id, ['role' => Organization::ROLE_MEMBER]);

    $app = App::factory()->create([
        'user_id' => $owner->id,
        'organization_id' => $organization->id,
    ]);

    $this->deleteJson(route('api.apps.destroy', [
        'organization' => $organization->id,
        'app' => $app->id,
    ]))
        ->assertForbidden();
});

test('user needs to be logged in to delete an app', function () {
    $this->withMiddleware(Authenticate::class);
    $organization = Organization::factory()->create();

    $this->deleteJson(route('api.apps.destroy', [
        'organization' => $organization->id,
        'app' => 1,
    ]))->assertUnauthorized();
});
