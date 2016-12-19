<?php namespace App\Providers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap any application services.
	 */
	public function boot()
	{
		Validator::extend('same_password', function ($attr, $val, $params, $validator) {
			$user = User::find($params[0]);
			return Hash::make($val) == $user->password;
		});
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
