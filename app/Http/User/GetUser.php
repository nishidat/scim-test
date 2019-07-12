<?php

namespace App\User;

use App\Model\User;
use Log;

class GetUser
{
    /**
    * [getByEmail emailによるユーザー取得]
    * @param  string $email
    * 
    * @return User|null
    */
    public function getByEmail( string $email ): ?User
    {
        return User::where( 'email', $email )->first();
    }
    
    /**
    * [getByScimId scim_idによるユーザー取得]
    * @param  string $scim_id
    * 
    * @return User|null
    */
    public function getByScimId( string $scim_id ): ?User
    {
        return User::where( 'scim_id', $scim_id )->first();
    }
}