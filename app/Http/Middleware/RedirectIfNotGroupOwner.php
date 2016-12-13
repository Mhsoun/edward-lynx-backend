<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

/**
* A middleware for redirecting if the signed in user is not the owner of a group. Expects a id parameter.
*/
class RedirectIfNotGroupOwner
{
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
		$groupId = $request->id;
		$group = \App\Models\Group::find($groupId);

		if ($group == null) {
			return redirect(url('/home'));
		}

		$isOwner = false;

		if (!$this->auth->guest()) {
			$isOwner = $this->auth->user()->id == $group->ownerId || $this->auth->user()->isAdmin;
		}

		if (!$isOwner) {
			return redirect(url('/group', $groupId));
		}

		return $next($request);
	}
}
