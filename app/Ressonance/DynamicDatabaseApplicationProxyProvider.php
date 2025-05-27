<?php

namespace App\Ressonance;

use Illuminate\Support\Collection;
use Laravel\Reverb\Contracts\ApplicationProvider;
use Laravel\Reverb\Application;

class DynamicDatabaseApplicationProxyProvider implements ApplicationProvider
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
     */
    public function all(): Collection
    {
        return app(DatabaseApplicationProvider::class)->all();
    }
    /**
     * Find an application instance by ID.
     *
     * @throws \Laravel\Reverb\Exceptions\InvalidApplication
     */
    public function findById(string $id): Application
    {
        return app(DatabaseApplicationProvider::class)->findById($id);
    }

    /**
     * Find an application instance by key.
     *
     * @throws \Laravel\Reverb\Exceptions\InvalidApplication
     */
    public function findByKey(string $key): Application
    {
        return app(DatabaseApplicationProvider::class)->findByKey($key);
    }

    /**
     * Find an application instance.
     *
     * @throws \Laravel\Reverb\Exceptions\InvalidApplication
     */
    public function find(string $key, mixed $value): Application
    {
        return app(DatabaseApplicationProvider::class)->find($key, $value);
    }
}