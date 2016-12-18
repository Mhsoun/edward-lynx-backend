<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;
use Symfony\Component\HttpFoundation\Cookie;

class VerifyCsrfToken extends BaseVerifier {
	private $execpt = [
		'email-bounced',
		'api/v1/*',
		'oauth/*'
	];

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		foreach($this->execpt as $route) {
			if ($request->is($route)) {
				return $next($request);
			}
		}

		return parent::handle($request, $next);
	}
}
