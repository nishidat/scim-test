<?php

namespace App\Http\Middleware;

use Closure;
use Log;

class RequestLog
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
        Log::debug('============Request '.$request->method().' '.$request->query("q", "/").' '.http_build_query($query).' Start=============');
        Log::debug($request->all());
        Log::debug('============Request '.$request->method().' '.$request->query("q", "/").' '.http_build_query($query).' End=============');
        return $next($request);
    }
}