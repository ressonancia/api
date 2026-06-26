<?php

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;

test('user can update its own name', function () {
    $user = $this->login();

    $response = $this->patchJson(route('api.account.update'), [
        'name' => 'Updated Name',
    ]);

    $response->assertStatus(Response::HTTP_OK)->assertJson([
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => $user->email,
    ]);

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'name' => 'Updated Name',
    ]);
});

test('only name can be updated on account', function () {
    $user = $this->login();

    $response = $this->patchJson(route('api.account.update'), [
        'name' => 'Only Name Updated',
        'email' => 'new-email@example.com',
    ]);

    $response->assertStatus(Response::HTTP_OK)
        ->assertJsonPath('name', 'Only Name Updated')
        ->assertJsonPath('email', $user->email);

    $this->assertDatabaseHas(User::class, [
        'id' => $user->id,
        'name' => 'Only Name Updated',
        'email' => $user->email,
    ]);
});

test('user needs to be logged in to update account', function () {
    $this->withMiddleware(Authenticate::class);

    $this->patchJson(route('api.account.update'))->assertUnauthorized();
});

test('name is required to update account', function () {
    $this->login();

    $this->patchJson(route('api.account.update'), [])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['name'])
        ->assertJsonFragment([
            'name' => ['The name field is required.'],
        ]);
});
