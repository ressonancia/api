<?php

namespace Database\Seeders;

use App\Console\Commands\Install;
use App\Models\App;
use App\Models\Organization;
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

        $organizations = Organization::factory()->times(2)->create();

        $user->organizations()->attach($organizations->first()->id, [
            'role' => Organization::ROLE_ADMIN,
        ]);

        $user->organizations()->attach($organizations->last()->id, [
            'role' => Organization::ROLE_MEMBER,
        ]);

        Client::truncate();

        Install::installOauthClients();

        App::factory()->times(20)->create([
            'organization_id' => $user->organizations()->first()->id,
        ]);
    }
}
