<?php

namespace App\Providers;

use App\Models\App;
use App\Models\User;
use App\Ressonance\Console\Commands\StartServer;
use App\Ressonance\DatabaseApplicationProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Console\Application;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        resolve(ApplicationManager::class)->extend('database', function () {
            return new DatabaseApplicationProvider(
                App::get()->collect()
            );
        });

        Passport::enablePasswordGrant();

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject(config('app.name') . '::Verify Email Address')
                ->greeting('THANKS FOR SIGNING UP!')
                ->line('Verify your E-mail Address clicking at the button bellow.')
                ->action('Verify Email Address', $url)
                ->line('If you did not create an account, no further action is required.');
        });

        VerifyEmail::createUrlUsing(function (object $notifiable) {
    
            $url = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return config('app.spa_url') . '/email-verification?'
                . http_build_query(['route' => rtrim(strtr(base64_encode($url), '+/', '-_'), '=')]);
        });

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return config('app.spa_url') . '/reset-password?token='.$token;
        });

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
