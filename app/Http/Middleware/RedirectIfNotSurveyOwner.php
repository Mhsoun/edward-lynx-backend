<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Log;

/**
* A middleware for redirecting if the signed in user is not the owner of a survey. Expects a id parameter.
*/
class RedirectIfNotSurveyOwner
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
		$surveyId = $request->id;
		$survey = \App\Models\Survey::find($surveyId);

		if ($survey == null) {
			return redirect(action('SurveyController@notFound'));
		}

		$isOwner = false;

		if (!$this->auth->guest()) {
			$isOwner = $this->auth->user()->id == $survey->ownerId || $this->auth->user()->isAdmin;
		}

		if (!$isOwner) {
			if ($request->ajax()) {
				return response()->json([
	                'success' => false
	            ]);
			} else {
				return redirect(action('SurveyController@index'));
			}
		}

		return $next($request);
	}
}
