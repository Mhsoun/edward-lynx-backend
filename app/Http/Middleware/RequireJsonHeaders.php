<?php

namespace App\Http\Middleware;

use Closure;
use Request;

class RequireJsonHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $accept = Request::header('accept');
        $content = Request::header('content-type');
        $json = 'application/json';

        if ($accept != $json) {
            return response()->json([
                'error'     => 'Unsupported Media Type',
                'message'   => 'Missing Accept header.'
            ], 415);
        }

        if (!$request->isMethod('get') && $content != $json) {
            return response('Unsupported Media Type', 415);
        }
        
        return $next($request);
    }
}
