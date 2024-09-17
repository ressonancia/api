<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\App>
 */
class AppFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'app_id' => (string) Str::uuid(),
            'app_key' => Str::lower(
                Str::random(20)
            ),
            'app_secret' => Str::lower(
                Str::random(20)
            ),
        ];
    }
}
