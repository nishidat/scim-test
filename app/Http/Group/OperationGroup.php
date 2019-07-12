<?php

namespace App\Group;

use App\Model\Group;
use Log;

class OperationGroup
{
    /**
    * [create グループ情報登録]
    * @param  array    $data        [登録内容]
    * 
    * @return Group    $groups       [groupsテーブルオブジェクト]
    */
    public function create( array $data ): Group
    {
        Log::debug( 'グループ情報登録内容' );
        Log::debug( $data );
        $scim_id = hash( 'ripemd160', $data['externalId'] );
        $groups = Group::create
        (
            [
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
        
        if ( !empty( $scim_id ) )
        {
            $groups = Group::where( 'scim_id', $scim_id )->first();
        }
        else
        {
            $groups = Group::where( 'group_name', $data['displayName'] )->first();
        }
        if ( isset( $data['displayName'] ) )
        {
            $groups->group_name = $data['displayName'];
        }
        $groups->save();
        Log::debug( 'グループ情報更新完了' );
        
        return $groups;
    }
    
    /**
    * [deleteGroupByScimId scim_idによるグループ削除]
    * @param  string  $scim_id
    * 
    * @return boolean
    */
    public function deleteGroupByScimId( string $scim_id ): boolean
    {
        $return = false;
        if ( Group::where( 'scim_id', $scim_id )->delete() > 0 ) 
        {
            $return = true;
        }
        
        return $return;
    }
}