<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

/**
* A middleware for redirecting if the signed in user is not an admin.
*/
class RedirectIfNotAdmin {
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$this->auth->guest()) {
            $isAdmin = $this->auth->user()->isAdmin;
        }

        if (!$isAdmin) {
            return redirect(url('/home'));
        }

        return $next($request);
    }
}
