<?php namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel {

	/**
	 * The application's global HTTP middleware stack.
	 *
	 * @var array
	 */
	protected $middleware = [
		\Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
		\App\Http\Middleware\SetLocale::class
	];

	/**
	 * The application's route middleware grops.
	 * 
	 * @var array
	 */
	protected $middlewareGroups = [
		'web' => [
			\App\Http\Middleware\EncryptCookies::class,
			\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
			\Illuminate\Session\Middleware\StartSession::class,
			\Illuminate\View\Middleware\ShareErrorsFromSession::class,
			\App\Http\Middleware\VerifyCsrfToken::class,
			\Illuminate\Routing\Middleware\SubstituteBindings::class
		],

		'api' => [
			'auth:api',
			'bindings',
			\App\Http\Middleware\RequireJsonHeaders::class
		],

		'api_public' => [
			'throttle',
			'bindings',
			\App\Http\Middleware\RequireJsonHeaders::class
		]
	];

	/**
	 * The application's route middleware.
	 *
	 * @var array
	 */
	protected $routeMiddleware = [
		'auth'			=> \Illuminate\Auth\Middleware\Authenticate::class,
		'auth.basic'	=> \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
		'bindings'		=> \Illuminate\Routing\Middleware\SubstituteBindings::class,
		'can'			=> \Illuminate\Auth\Middleware\Authorize::class,
		'guest' 		=> \App\Http\Middleware\RedirectIfAuthenticated::class,
		'throttle'		=> \Illuminate\Routing\Middleware\ThrottleRequests::class,

		'group-owner' => '\App\Http\Middleware\RedirectIfNotGroupOwner',
		'survey-owner' => '\App\Http\Middleware\RedirectIfNotSurveyOwner',
		'admin' => '\App\Http\Middleware\RedirectIfNotAdmin'
	];
}
