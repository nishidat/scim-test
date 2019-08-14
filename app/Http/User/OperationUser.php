<?php

namespace App\User;

use App\Model\User;
use App\Api\ApiClient;
use Illuminate\Support\Facades\Hash;
use Log;
use DB;

class OperationUser
{
    /**
    * [create ユーザー情報登録]
    * @param  array        $data        [登録内容]
    * 
    * @return User|null    $users       [usersテーブルオブジェクト]
    */
    public function create( array $data ): ?User
    {
        Log::debug( 'ユーザー情報登録内容' );
        Log::debug( $data );
        $scim_id = hash( 'ripemd160', $data['externalId'] );
        if ( !isset( $data['name'] ) ) 
        {
            $data['name']['givenName'] = '';
            $data['name']['familyName'] = '';
            $data['name']['formatted'] = '';
        }
        DB::beginTransaction();
        $users = User::create
        (
            [
                'scim_id' => $scim_id,
                'external_id' => $data['externalId'],
                'tenant_id' => $data['tenant_id'],
                'display_name' => $data['displayName'],
                'given_name' => $data['name']['givenName'],
                'family_name' => $data['name']['familyName'],
                'user_name' => $data['name']['formatted'],
                'email' => $data['userName'],
                'active' => $data['active'],
                'password' => Hash::make('password'),
            ]
        );
        $api_client = new ApiClient();
        if ( $api_client->createUser( $data ) === false ) 
        {
            DB::rollback();
            return null;
        }
        
        DB::commit();
        Log::debug( 'ユーザー情報登録完了' );
        
        return $users;
    }
    
    /**
    * [update ユーザー情報更新]
    * @param  array            $data        [更新内容]
    * @param  string|null      $scim_id     [scim_id]
    * 
    * @return User|null        $users       [usersテーブルオブジェクト]
    */
    public function update( array $data, ?string $scim_id = null ): ?User
    {
        Log::debug( 'ユーザー情報更新内容' );
        Log::debug( $data );
        DB::beginTransaction();
        
        if ( !empty( $scim_id ) )
        {
            $users = User::where( 'scim_id', $scim_id )->first();
        }
        else
        {
            $users = User::where( 'email', $data['userName'] )->first();
        }
        $data['olduserName'] = $users->email;
        if ( isset( $data['active'] ) )
        {
            $users->active = $data['active'];
        }
        if ( isset( $data['formatted'] ) )
        {
            $users->user_name = $data['formatted'];
        } 
        if ( isset( $data['givenName'] ) )
        {
            $users->given_name = $data['givenName'];
        }
        if ( isset( $data['displayName'] ) )
        {
            $users->display_name = $data['displayName'];
        }
        if ( isset( $data['groupId'] ) )
        {
            $users->group_id = $data['groupId'];
        } 
        if ( isset( $data['familyName'] ) )
        {
            $users->family_name = $data['familyName'];
        } 
        if ( isset( $data['externalId'] ) )
        {
            $users->external_id = $data['externalId'];
        } 
        if ( isset( $data['userName'] ) )
        {
            $users->email = $data['userName'];
            $api_client = new ApiClient();
            if ( $api_client->updateUser( $data ) === false ) 
            {
                DB::rollback();
                return null;
            }
        }

        $users->save();
        
        DB::commit();
        Log::debug( 'ユーザー情報更新完了' );
        
        return $users;
    }
    
    /**
    * [deleteUserByScimId scim_idによるユーザー削除]
    * @param  string $scim_id
    * 
    * @return boolean
    */
    public function deleteUserByScimId( string $scim_id, string $tenant_id ): bool
    {
        $return = true;
        DB::beginTransaction();
        if ( User::where( 'scim_id', $scim_id )->delete() <= 0 ) 
        {
            DB::rollback();
            return false;
        }
        $users = User::where( 'scim_id', $scim_id )->first();
        $api_client = new ApiClient();
        if ( $api_client->deleteUser( $users->email, $tenant_id ) === false ) 
        {
            DB::rollback();
            return null;
        }
        DB::commit();
        
        return $return;
    }
}