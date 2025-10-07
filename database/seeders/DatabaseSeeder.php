<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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

        DB::table('oauth_clients')->insert([
            'name' => 'First Party SPA',
            'secret' => 'Dq9p296oaZtbaH7HX8v9gD1nuHaWmLSlox8a9Bfk',
            'provider' => 'users',
            'redirect' => 'http://localhost',
            'password_client' => 1,
            'personal_access_client' => 0,
            'revoked' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('oauth_clients')->insert([
            'name' => 'Ressonance Personal Access Client',
            'secret' => 'qhTkBLYHfqtWRptHfHadOBs3cKM1jmZIkchqSKI2',
            'provider' => null,
            'redirect' => 'http://localhost',
            'password_client' => 0,
            'personal_access_client' => 1,
            'revoked' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        App::factory()->times(20)->create([
            'user_id' => $user->id,
        ]);
    }
}
