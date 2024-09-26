<?php

namespace App\Ressonance;

use App\Models\App;
use App\Ressonance\DatabaseApplicationProvider;
use Laravel\Reverb\ApplicationManager as ReverbApplicationManager;

class ApplicationManager extends ReverbApplicationManager
{
    /**
     * Create an instance of the configuration driver.
     */
    public function createDatabaseDriver(): DatabaseApplicationProvider
    {
        return new DatabaseApplicationProvider(
            App::get()->collect()
        );
    }
}
