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
    public function handle( $request, Closure $next )
    {
        Log::debug( '============Request ' . $request->method() . ' ' . $request->path() . ' Start=============' );
        Log::debug( $request->all() );
        Log::debug( '============Request ' . $request->method() . ' ' . $request->path() . ' End=============' );
        
        return $next( $request );
    }
}