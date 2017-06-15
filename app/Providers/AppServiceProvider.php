<?php namespace App\Providers;

use App\Models\User;
use App\Models\SurveyRecipient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Routing\UrlGenerator;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap any application services.
	 */
	public function boot(UrlGenerator $url)
	{
		Validator::extend('same_password', function ($attr, $val, $params, $validator) {
			$user = User::find($params[0]);
			return Hash::check($val, $user->password);
		});
        
        // Checks if a value is a valid ISO8601 date string.
        Validator::extend('isodate', function ($attr, $val, $params, $validator) {
            $pattern = '/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/';
            return preg_match($pattern, $val);
        });

        // Force SSL if APP_FORCE_SSL is present
        if (env('APP_FORCE_HTTPS') === true) {
        	$url->forceSchema('https');
        }
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
