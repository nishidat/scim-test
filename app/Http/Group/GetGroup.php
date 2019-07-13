<?php

namespace App\Group;

use App\Model\Group;
use Log;

class GetGroup
{
    /**
    * [getByGroupName group_nameによるグループ取得]
    * @param  string $group_name
    * 
    * @return Group|null
    */
    public function getByGroupName( string $group_name ): ?Group
    {
        return Group::where( 'group_name', $group_name )->first();
    }
    
    /**
    * [getByScimId scim_idによるグループ取得]
    * @param  string $scim_id
    * 
    * @return Group|null
    */
    public function getByScimId( string $scim_id ): ?Group
    {
        Log::debug($scim_id);
        return Group::where( 'scim_id', $scim_id )->first();
    }
}