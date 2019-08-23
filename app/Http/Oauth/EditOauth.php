<?php

namespace App\Oauth;

use App\Model\Oauth;
use Log;

class EditOauth
{
    private const TOKEN_LENGTH = 40;
    /**
    * [getBytenantID tenantIdによるtoken取得]
    * @param  string $tenant_id
    * 
    * @return string $token
    */
    public function getBytenantID( string $tenant_id ): string
    {
        return Oauth::where( 'tenant_id', $tenant_id )->value( 'token' );
    }
    
    /**
    * [createTokenBytenantID tenantIdによるtoken作成]
    * @param  string $tenant_id
    * 
    * @return bool
    */
    public function createTokenBytenantID( string $tenant_id ): bool
    {
        Oauth::create
        (
            [
                'tenant_id' => $tenant_id,
                'token' => substr( bin2hex( random_bytes( self::TOKEN_LENGTH ) ), 0, self::TOKEN_LENGTH ),
            ]
        );
        return true;
    }
}