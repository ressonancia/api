<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ressonance:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates users and all necesary structure to run ressonance im production for selfhosted instances.';

    /**
     * Email to be seeded at the first run.
     *
     * @var string
     */
    protected $email = 'admin@ressonance.com';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->shouldInstallRessonance()) {
            self::installOauthClients();

            $password = uniqid();
            User::create([
                'name' => 'Admin User',
                'email' => $this->email,
                'email_verified_at' => now(),
                'password' => bcrypt($password),
            ]);

            $this->info('User: '.User::get()->last()->email);
            $this->info('Password: '.$password);
        }

        $this->info('Access ressonance at port 80.');
        $this->info('Access ressonance api port 8000.');
        $this->info('Ressonance websockets API is running at port '
            .data_get(config('reverb'), 'servers.reverb.port').'.');
    }

    private function shouldInstallRessonance(): bool
    {
        return is_null(User::whereEmail($this->email)->first());
    }

    public static function installOauthClients(): void
    {
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
    }
}
