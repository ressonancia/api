<?php

use App\Models\App;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('user can delete account', function () {
    $user = $this->login();

    $response = $this->deleteJson(route('api.users.destroy'));
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('users', [
        'id' => $user->id
    ]);
});

test('user should delete apps before delete the account', function () {
    $user = $this->login();

    App::factory()->create([
        'user_id' => $user->id
    ]);

    $response = $this->deleteJson(route('api.users.destroy'));
    $response->assertStatus(Response::HTTP_PRECONDITION_FAILED);
    $response->assertJsonPath(
        'message',
        'The user should delete all apps before deleting the account'
    );

    $this->assertDatabaseHas('users', [
        'id' => $user->id
    ]);
});


test('user needs to be logged in to delete an account', function () {
    $this->withMiddleware(Authenticate::class);

    $this->deleteJson(route('api.users.destroy', ['app' => 1]))->assertUnauthorized();
});