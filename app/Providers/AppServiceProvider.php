<?php

namespace App\Providers;

use App\Models\App;
use App\Ressonance\DatabaseApplicationProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\ServerProviderManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        resolve(ServerProviderManager::class)->extend('database', function () {
            return new DatabaseApplicationProvider(
                App::get()->collect()
            );
        });
    }
}
