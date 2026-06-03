<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        return [
            'organization_id' => (string) Str::uuid(),
            'inviter_id' => rand(1, 100),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'role' => fake()->randomElement([Organization::ROLE_ADMIN, Organization::ROLE_MEMBER]),
            'expires_at' => now()->addDay(),
            'joined_at' => null,
        ];
    }
}
