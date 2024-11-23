<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
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

        User::factory()->create([
            'name' => 'Zaphod Beeblebrox',
            'email' => 'zaphod@l30.space',
            'password' => bcrypt('secret')
        ]);

        Client::truncate();
        Artisan::call('passport:client --password --name "First Party SPA" --provider users');

        App::factory()->times(20)->create();
    }
}
