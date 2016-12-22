<?php namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler {

	/**
	 * A list of the exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $dontReport = [
		AuthorizationException::class,
		HttpException::class,
		ModelNotFoundException::class,
		ValidationException::class,
	];

	/**
	 * Map of HTTP status codes into status text.
	 * 
	 * @var array
	 */
	protected $codeToText = [
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		409 => 'Conflict',
		500 => 'Internal Server Error'
	];

	/**
	 * Report or log an exception.
	 *
	 * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	public function report(Exception $e)
	{
		return parent::report($e);
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\Response
	 */
	public function render($request, Exception $e)
	{
		$e = $this->prepareException($e);

        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        } elseif ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e, $request);
        } elseif ($e instanceof HttpException && $request->expectsJson()) {
        	return $this->convertHttpExceptionToApiResponse($e);
        } elseif ($e instanceof ApiException) {
        	return $this->convertApiExceptionToResponse($e);
        }

        return $this->prepareResponse($request, $e);
	}

	/**
	 * Convert an authentication exception into an unauthenticated response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Illuminate\Auth\AuthenticationException  $exception
	 * @return \Illuminate\Http\Response
	 */
	protected function unauthenticated($request, AuthenticationException $exception)
	{
	    if ($request->expectsJson()) {
	        return response()->json(['error' => 'Unauthenticated.'], 401);
	    }

	    return redirect()->guest('login');
	}

	/**
	 * Puts validation errors under the error key so it is easily detectable.
	 * 
	 * @param ValidationException $e
	 * @param Request $request
	 * @return Response
	 */
	protected function convertValidationExceptionToResponse(ValidationException $e, $request)
	{
		if ($e->response) {

			// If for some reason we already have a response override it.
			if ($request->expectsJson()) {
				$e->response = $this->generateValidationErrorJson($e);
			}

            return $e->response;
        }

        $errors = $e->validator->errors()->getMessages();
        if ($request->expectsJson()) {
        	$json = $this->generateValidationErrorJson();
            return response()->json($json, 422);
        }

        return redirect()->back()->withInput($request->input())->withErrors($errors);
	}

	/**
	 * Converts a HTTP exception to an API error response.
	 * 
	 * @param  HttpException $e
	 * @return \Illuminate\Http\Response
	 */
	protected function convertHttpExceptionToApiResponse(HttpException $e)
	{
		$code = $e->getStatusCode();
		return response()->json([
			'error'		=> $this->codeToText[$code],
			'message'	=> $e->getMessage()
		], $code);
	}

	/**
	 * Converts an api exception to a api error response.
	 * 
	 * @param  ApiException $e
	 * @return \Illuminate\Http\Response
	 */
	protected function convertApiExceptionToResponse(ApiException $e)
	{
		$code = $e->getStatusCode();
		return response()->json([
			'error'		=> $this->codeToText[$code],
			'message'	=> $e->getMessage()
		], $code);
	}

	/**
	 * Generates the validation error JSON that will be returned
	 * on error.
	 * 
	 * @param ValidationException $e 
	 * @return JSONResponse
	 */
	protected function generateValidationErrorJson(ValidationException $e)
	{
		return response()->json([
			'error'				=> 'validation_error',
			'validation_errors'	=> $e->validator->errors()->getMessages()
		], 422);
	}
}
