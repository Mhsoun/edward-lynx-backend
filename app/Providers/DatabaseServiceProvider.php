<?php

namespace App\Providers;

use App\Models\DevelopmentPlanGoal;
use Illuminate\Support\ServiceProvider;
use App\Models\DevelopmentPlanGoalAction;
use App\Observers\DevelopmentPlanGoalObserver;
use App\Observers\DevelopmentPlanGoalActionObserver;
use Illuminate\Database\Eloquent\Relations\Relation;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return  void
     */
    public function boot()
    {   
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
        DevelopmentPlanGoalAction::observe(DevelopmentPlanGoalActionObserver::class);
        DevelopmentPlanGoal::observe(DevelopmentPlanGoalObserver::class);
    }
}
