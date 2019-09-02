<?php

namespace App\Group;

use App\Model\Group;
use App\Group\GetGroup;
use Log;

class OperationGroup
{
    /**
    * [create グループ情報登録]
    * @param  array    $data        [登録内容]
    * 
    * @return Group    $groups       [groupsテーブルオブジェクト]
    */
    public function create( array $data, string $tenant_id ): Group
    {
        Log::debug( 'グループ情報登録内容' );
        Log::debug( $data );
        $scim_id = hash( 'ripemd160', $data['externalId'] );
        $groups = Group::create
        (
            [
                'tenant_id' => $tenant_id,
                'scim_id' => $scim_id,
                'external_id' => $data['externalId'],
                'group_name' => $data['displayName'],
            ]
        );
        Log::debug( 'グループ情報登録完了' );
        
        return $groups;
    }
    
    /**
    * [update グループ情報更新]
    * @param  array        $data        [更新内容]
    * @param  string|null  $scim_id     [scim_id]
    * 
    * @return Group        $groups      [groupsテーブルオブジェクト]
    */
    public function update( array $data, ?string $scim_id = null ): Group
    {
        Log::debug( 'グループ情報更新内容' );
        Log::debug( $data );
        
        $get_group = new GetGroup();
        
        if ( !empty( $scim_id ) )
        {
            $groups_object = $get_group->getByScimId( $scim_id, $data['tenant_id'] );
        }
        else
        {
            $groups_object = $get_group->getByGroupName( $data['displayName'], $data['tenant_id'] );
        }
        if ( isset( $data['displayName'] ) )
        {
            $groups_object->group_name = $data['displayName'];
        }
        $groups_object->save();
        Log::debug( 'グループ情報更新完了' );
        
        return $groups_object;
    }
    
    /**
    * [deleteGroupByScimId scim_idによるグループ削除]
    * @param  string  $scim_id
    * 
    * @return boolean
    */
    public function deleteGroupByScimId( string $scim_id, string $tenant_id ): boolean
    {
        $return = true;
        if ( Group::where( 'scim_id', $scim_id )->where( 'tenant_id', $tenant_id )->delete() <= 0 ) 
        {
            $return = false;
        }
        
        return $return;
    }
}