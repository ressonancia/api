<?php

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

test('user can change its password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('milho'),
    ]);

    $this->actingAs($user);

    Log::shouldReceive('info')
        ->once()
        ->with('Password Changed For: ' . $user->email);
    
    $response = $this->postJson(route('api.password.change'), [
        'password' => 'Pipoka123!',
        'password_confirmation' => 'Pipoka123!',
    ]);

    $response->assertStatus(Response::HTTP_OK)->assertJson([
        'message' => 'Password has changed'
    ]);

    $this->assertTrue(Hash::check('Pipoka123!', $user->fresh()->password));
});

test('user needs to be logged in to change its password', function () {
    $this->withMiddleware(Authenticate::class);
    $this->postJson(route('api.password.change'))->assertUnauthorized();
});