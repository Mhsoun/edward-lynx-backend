<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Define database morph mappings.
        Relation::morphMap([
            'users'         => \App\Models\User::class,
            'recipients'    => \App\Models\Recipient::class
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
