<?php

namespace App\Http\Middleware;

use Closure;
use Log;
use App\Oauth\EditOauth;

class OAuthCheck
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
        $tenant_id = $request->route()->parameter('tenant_id');
        $edit_auth = new EditOauth();
        $token = $edit_auth->getBytenantID( $tenant_id );
    
        $headers = getallheaders();    
        if ( !isset( $headers['Authorization'] ) ) 
        {
            return response( 'Authorization', 400 );
        }
        if( str_replace('Bearer ', '', $headers['Authorization'] ) != $token )
        {
            return response( 'Authorization', 400 );
        }
        
        return $next($request);
    }
}
