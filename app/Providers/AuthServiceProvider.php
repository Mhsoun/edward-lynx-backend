<?php

namespace App\Providers;

use Carbon\Carbon;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Models\Survey'             => 'App\Policies\SurveyPolicy',
        'App\Models\InstantFeedback'    => 'App\Policies\InstantFeedbackPolicy',
        'App\Models\DevelopmentPlan'    => 'App\Policies\DevelopmentPlanPolicy',
        'App\Models\User'               => 'App\Policies\UserPolicy',
        'App\Models\QuestionCategory'   => 'App\Policies\QuestionCategoryPolicy'
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Configure passport
        Passport::routes();
        Passport::tokensExpireIn(Carbon::now()->addDays(1));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(2));
    }
}