<?php

use App\Models\App;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('user can delete an app', function () {
    $user = $this->login();

    $app = App::factory()->create([
        'user_id' => $user->id
    ]);

    $appToKeep = App::factory()->create([
        'user_id' => $user->id
    ]);

    $response = $this->deleteJson(route('api.apps.destroy', $app->id));
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('apps', [
        'id' => $app->id,
        'deleted_at' => null
    ]);

    $this->assertDatabaseHas('apps', [
        'id' => $appToKeep->id,
        'deleted_at' => null
    ]);

});

test('user cannot delete an app from another user', function () {
    $user = $this->login();

    $app = App::factory()->create([
        'user_id' => $user->id + 1
    ]);

    $this->deleteJson(route('api.apps.destroy', $app->id))
        ->assertForbidden();
});

test('user needs to be logged in to delete an app', function () {
    $this->withMiddleware(Authenticate::class);

    $this->deleteJson(route('api.apps.destroy', ['app' => 1]))->assertUnauthorized();
});