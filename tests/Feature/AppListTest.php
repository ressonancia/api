<?php

use App\Models\App;
use Illuminate\Http\Response;

test('user can list apps', function () {
    $app = App::factory()->create();

    $response = $this->getJson(route('api.apps.index'));

    $this->assertEquals(
        (array) $response->getData()->data[0],
        $app->toArray()
    );

    $response->assertStatus(Response::HTTP_OK);
});
