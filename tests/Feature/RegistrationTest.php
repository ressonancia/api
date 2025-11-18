<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_register()
    {
        Event::fake();

        $response = $this->postJson(route('api.register'), [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'User created successfully. Please check your email for verification.']);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        Event::assertDispatched(Registered::class);
    }

    /** @test */
    public function a_user_cannot_register_with_an_existing_email()
    {
        User::factory()->create(['email' => 'john.doe@example.com']);

        $response = $this->postJson(route('api.register'), [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function registration_request_is_throttled()
    {
        for ($i = 0; $i < 60; $i++) {
            $this->postJson(route('api.register'), []);
        }

        $response = $this->postJson(route('api.register'), []);

        $response->assertStatus(429);
    }
}
