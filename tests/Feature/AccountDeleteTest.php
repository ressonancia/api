<?php

use App\Models\App;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('user can delete account', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $app = App::factory()->create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
    ]);

    $response = $this->deleteJson(route('api.users.destroy'));
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);

    $this->assertDatabaseMissing('apps', [
        'id' => $app->id,
        'deleted_at' => null,
    ]);
});

test('user is detached from organizations where it is not owner before deleting account', function () {
    $user = $this->login();
    $owner = User::factory()->create();
    $organization = $owner->organizations()->first();
    $organization->users()->attach($user->id, ['role' => Organization::ROLE_MEMBER]);

    $this->deleteJson(route('api.users.destroy'))
        ->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $user->id,
    ]);
});

test('user needs to be logged in to delete an account', function () {
    $this->withMiddleware(Authenticate::class);

    $this->deleteJson(route('api.users.destroy', ['app' => 1]))->assertUnauthorized();
});
