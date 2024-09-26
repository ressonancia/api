<?php

namespace App\Ressonance;

use Illuminate\Support\Collection;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Exceptions\InvalidApplication;
use Laravel\Reverb\Application;

class DatabaseApplicationProvider implements ApplicationProvider
{
    /**
     * Create a new config provider instance.
     */
    public function __construct(protected Collection $applications)
    {
        //
    }

    /**
     * Get all of the configured applications as Application instances.
     *
     * @return \Illuminate\Support\Collection<\Laravel\Reverb\Application>
     */
    public function all(): Collection
    {
        return $this->applications->map(function ($app) {
            return $this->findById($app['app_id']);
        });
    }
    /**
     * Find an application instance by ID.
     *
     * @throws \Laravel\Reverb\Exceptions\InvalidApplication
     */
    public function findById(string $id): Application
    {
        return $this->find('app_id', $id);
    }

    /**
     * Find an application instance by key.
     *
     * @throws \Laravel\Reverb\Exceptions\InvalidApplication
     */
    public function findByKey(string $key): Application
    {
        return $this->find('app_key', $key);
    }

    /**
     * Find an application instance.
     *
     * @throws \Laravel\Reverb\Exceptions\InvalidApplication
     */
    public function find(string $key, mixed $value): Application
    {
        $app = $this->applications->firstWhere($key, $value);

        if (! $app) {
            throw new InvalidApplication;
        }

        return new Application(
            $app['app_id'],
            $app['app_key'],
            $app['app_secret'],
            $app['ping_interval'] ?? 60,
            $app['activity_timeout'] ?? 30,
            $app['allowed_origins'] ?? ['*'],
            $app['max_message_size'] ?? 10_000,
            $app['options'] ?? [],
        );
    }
}
