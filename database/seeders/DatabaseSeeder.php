<?php

namespace Database\Seeders;

use App\Console\Commands\Install;
use App\Models\App;
use App\Models\User;
use Illuminate\Database\Seeder;
use Laravel\Passport\Client;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::truncate();

        User::factory()->create([
            'name' => 'Jacob Lee',
            'email' => 'jacob@l30.space',
        ]);

        $user = User::factory()->create([
            'name' => 'Zaphod Beeblebrox',
            'email' => 'zaphod@l30.space',
            'password' => bcrypt('secret'),
        ]);

        Client::truncate();

        Install::installOauthClients();

        App::factory()->times(20)->create([
            'user_id' => $user->id,
        ]);
    }
}
