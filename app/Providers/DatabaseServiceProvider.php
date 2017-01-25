<?php

namespace App\Providers;

use App\Models\DevelopmentPlanGoal;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return  void
     */
    public function boot()
    {
        // Define database morph mappings.
        Relation::morphMap([
            'users'         => \App\Models\User::class,
            'recipients'    => \App\Models\Recipient::class
        ]);
        
        $this->registerModelHooks();
    }

    /**
     * Register the application services.
     *
     * @return  void
     */
    public function register()
    {
        //
    }
    
    /**
     * Registers model hooks.
     *
     * @return  void
     */
    protected function registerModelHooks()
    {
        DevelopmentPlanGoal::deleted(function ($goal) {
            $goal->developmentPlan->updateGoalPositions();
        });
    }
}
