<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap any application services.
	 */
	public function boot()
	{
		//
	}

	/**
	 * Register any application services.
	 *
	 * This service provider is a great spot to register your various container
	 * bindings with the application. As you can see, we are registering our
	 * "Registrar" implementation here. You can add your own bindings too!
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind(
			'Illuminate\Contracts\Auth\Registrar',
			'App\Services\Registrar'
		);

		$this->app->singleton('\App\SendSurveyEmail', function($app)
		{
			return new \App\ActualSendSurveyEmail;
		});

		$this->app->singleton('SurveyEmailer', function($app)
		{
			return new \App\SurveyEmailer($app->make('\App\SendSurveyEmail'));
		});
	}
}
