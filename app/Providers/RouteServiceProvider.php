<?php namespace App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider {

	/**
	 * This namespace is applied to the controller routes in your routes file.
	 *
	 * In addition, it is set as the URL generator's root namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'App\Http\Controllers';

	/**
	 * Define your route model bindings, pattern filters, etc.
	 *
	 * @return void
	 */
	public function boot()
	{
		parent::boot();

		//
	}

	/**
	 * Define the routes for the application.
	 *
	 * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
	public function map(Router $router)
	{
		$this->mapApiRoutes();
		$this->mapWebRoutes();
	}

	protected function mapWebRoutes()
	{
		Route::group([
			'namespace'	=> $this->namespace,
			'middleware' => 'web'
		], function($router) {
			require base_path('routes/web.php');
		});
	}

	public function mapApiRoutes()
	{
		Route::group([
			'namespace'		=> $this->namespace . '\Api\V1',
			'middleware'	=> 'api',
			'prefix'		=> 'api/v1'
		], function($router) {
			require base_path('routes/api.php');
		});
	}

}
