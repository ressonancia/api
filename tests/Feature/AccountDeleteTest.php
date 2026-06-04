<?php

use App\Models\App;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('user can delete account', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $response = $this->deleteJson(route('api.users.destroy'));
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing(User::class, [
        'id' => $user->id,
    ]);

    $this->assertDatabaseMissing(Organization::class, [
        'id' => $organization->id,
    ]);

    $this->assertDatabaseMissing('organization_user', [
        'organization_id' => $organization->id,
    ]);
});

test('user cannot delete account while attached to a non-owned organization', function () {
    $user = $this->login();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $response = $this->deleteJson(route('api.users.destroy'));
    $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);
    $response->assertJsonPath(
        'message',
        'The user should leave all non-owned organizations before deleting the account'
    );

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);
});

test('user should delete apps before delete the account', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();
    $secondUserOrganization = Organization::factory()->create();
    $secondUserOrganization->users()->attach($user->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    App::factory()->create([
        'organization_id' => $secondUserOrganization->id,
    ]);

    $response = $this->deleteJson(route('api.users.destroy'));
    $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);
    $response->assertJsonPath(
        'message',
        'The user should delete all apps before deleting the account'
    );

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);
});

test('user needs to be logged in to delete an account', function () {
    $this->withMiddleware(Authenticate::class);

    $this->deleteJson(route('api.users.destroy', ['app' => 1]))->assertUnauthorized();
});
