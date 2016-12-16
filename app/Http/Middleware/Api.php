<?php

namespace App\Http\Middleware;

use Closure;
use Request;

class Api
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
        if ($accept != $json || $content != $json) {
            return response('Unsupported Media Type', 415);
        }
        
        return $next($request);
    }
}
