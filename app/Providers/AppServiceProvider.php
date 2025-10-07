<?php

namespace App\Providers;

use App\Models\App;
use App\Models\User;
use App\Ressonance\Console\Commands\StartServer;
use App\Ressonance\DatabaseApplicationProvider;
use App\Ressonance\DynamicDatabaseApplicationProxyProvider;
use Illuminate\Console\Application;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Reverb\ApplicationManager;
use Symfony\Component\HttpFoundation\IpUtils;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Passport::ignoreRoutes();

        if (! config('ressonance.self_hosted')) {
            $this->app->register(CloudServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_schema')) {
            URL::forceScheme('https');
        }

        resolve(ApplicationManager::class)->extend('database', function () {
            return new DynamicDatabaseApplicationProxyProvider(collect());
        });

        $this->app->singleton(DatabaseApplicationProvider::class, function () {
            return new DatabaseApplicationProvider(
                App::get()->collect()
            );
        });

        Passport::enablePasswordGrant();

        if ($this->app->runningInConsole()) {
            Application::starting(function ($artisan) {
                $artisan->resolveCommands([
                    StartServer::class,
                ]);
            });
        }

        Gate::define('viewPulse', function (?User $user) {
            $clientIp = request()->ip() ?? '';

            return IpUtils::checkIp(
                $clientIp,
                config('pulse.admin_ips')
            );
        });
    }
}
