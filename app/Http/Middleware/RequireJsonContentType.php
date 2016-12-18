<?php

namespace App\Http\Middleware;

use Closure;
use Request;

class RequireJsonContentType
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
        $content = Request::header('content-type');

        if (!$request->isMethod('get') && $content != 'application/json') {
            return response('Unsupported Media Type', 415);
        }
        
        return $next($request);
    }
}
