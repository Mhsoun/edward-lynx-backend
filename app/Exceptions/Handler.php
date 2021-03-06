<?php namespace App\Exceptions;

use Exception;
use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
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
        CustomValidationException::class,
		HttpException::class,
		ModelNotFoundException::class,
		ValidationException::class,
        SurveyMissingAnswersException::class
	];

	/**
	 * Map of HTTP status codes into status text.
	 * 
	 * @var array
	 */
	protected $codeToText = [
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
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
        if ($this->shouldReport($e)) {
            app('sentry')->captureException($e);
        }
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
        } elseif ($e instanceof CustomValidationException) {
            return $this->convertCustomValidationExceptionToResponse($e, $request);
        } elseif ($e instanceof HttpException && $request->expectsJson()) {
			return $this->convertHttpExceptionToJsonResponse($e);	
		} elseif ($e instanceof ApiException) {
        	return $this->convertApiExceptionToResponse($e);
        } elseif ($e instanceof SurveyMissingAnswersException) {
            return $this->convertSurveyMissingAnswersExceptionToResponse($e, $request);
		}

        return $this->prepareResponse($request, $e);
	}

	/**
	 * Handles exceptions not processed by other handlers.
	 * 
	 * Exceptions that fall here are usually code or logic errors
	 * so they are rendered as a HTTP 500 error.
	 *
	 * @param mixed $request
	 * @param Exception $e
	 * @return Illuminate\Http\Response
	 */
	public function prepareResponse($request, Exception $e)
	{
		$response = parent::prepareResponse($request, $e);
		
		if ($request->expectsJson()) {
			return $this->convertExceptionToJsonResponse($request, $e, 500);
		}

		return $response;
	}

	/**
	 * Converts an exception class name to a human-friendly name.
	 *
	 * @param Exception $e
	 * @return string
	 */
	protected function exceptionToHumanReadable(Exception $e)
	{
		$reflect = new ReflectionClass($e);
		$class = $reflect->getShortName();
		$name = snake_case($class);
		$name = str_replace('_', ' ', $name);
		$name = ucwords($name);

		return $name;
	}

	/**
	 * Converts an exception to a JSON error response.
	 * 
	 * @param mixed $request
	 * @param Exception $e
	 * @param integer $status
	 * @return Illuminate\Http\Response
	 */
	protected function convertExceptionToJsonResponse($request, $e, $status = 500)
	{
		if (config('app.debug')) {
			return response()->json([
				'error'		=> $this->exceptionToHumanReadable($e),
				'message'	=> $e->getMessage(),
				'source'	=> sprintf('%s:%d', $e->getFile(), $e->getLine()),
			], $status);
		} else {
			return response()->json([
				'error'		=> 'Internal Server Error',
			], $status);
		}
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
        $errors = $e->validator->errors()->getMessages();
        
		if ($e->response) {
			// If for some reason we already have a response override it.
			if ($request->expectsJson()) {
				$e->response = $this->generateValidationErrorJson($errors);
			}
            return $e->response;
        }

        if ($request->expectsJson()) {
        	$json = $this->generateValidationErrorJson($errors);
            return response()->json($json, 422);
        }

        return redirect()->back()->withInput($request->input())->withErrors($errors);
	}
    
	/**
	 * Puts validation errors under the error key so it is easily detectable.
	 * 
	 * @param   App\Exceptions\CustomValidationException    $e
	 * @param   Illuminate\Http\Request                     $request
	 * @return  Illuminate\Http\Response
	 */
	protected function convertCustomValidationExceptionToResponse(CustomValidationException $e, $request)
	{
        $errors = $e->errors()->getMessages();
        
        if ($request->expectsJson()) {
        	return $this->generateValidationErrorJson($errors);
        }

        return redirect()->back()->withInput($request->input())->withErrors($errors);
	}

	/**
	 * Converts a HTTP exception to a JSON error response.
	 * 
	 * @param  HttpException $e
	 * @return \Illuminate\Http\Response
	 */
	protected function convertHttpExceptionToJsonResponse(HttpException $e)
	{
		$code = $e->getStatusCode();
        $message = $e->getMessage();
        
        // Do not expose namespaces if we have a 404
        if ($code == 404) {
            $message = preg_replace('/\[App\\+Models\\+[^\]]+\]\./', '', $message);
        }
        
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
			'error'		=> $this->exceptionToHumanReadable($e),
			'message'	=> $e->getMessage(),
		], $code);
	}

	/**
	 * Generates the validation error JSON that will be returned
	 * on error.
	 * 
	 * @param   Illuminate\Support\MessageBag   $messages
	 * @return  JSONResponse
	 */
	protected function generateValidationErrorJson($messages)
	{
		return response()->json([
			'error'				=> 'validation_error',
			'validation_errors'	=> $messages
		], 422);
	}
    
    /**
     * Generates a response for surveys missing answers when marked as
     * final exception.
     *
     * @param   App\Exceptions\SurveyMissingAnswersException    $e
     * @param   Illuminate\Http\Request                         $request
     * @return  Illuminate\Http\Response
     */
    protected function convertSurveyMissingAnswersExceptionToResponse(SurveyMissingAnswersException $e, Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json($e, 422);
        }
	}
	

}
