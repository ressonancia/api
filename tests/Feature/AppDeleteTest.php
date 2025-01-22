<?php

use App\Models\App;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

test('user can delete an app', function () {
    $app = App::factory()->create();

    $response = $this->deleteJson(route('api.apps.destroy', $app->id));
    $response->assertStatus(Response::HTTP_NO_CONTENT);

    $this->assertDatabaseMissing('apps', [
        'id' => $app->id,
        'deleted_at' => null
    ]);

});


test('user needs to be logged in to create app', function () {
    $this->withMiddleware(Authenticate::class);

    $this->deleteJson(route('api.apps.destroy', ['app' => 1]))->assertUnauthorized();
});