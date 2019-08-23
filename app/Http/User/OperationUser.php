<?php

namespace App\User;

use App\Model\User;
use App\User\GetUser;
use App\Api\ApiClient;
use Illuminate\Support\Facades\Hash;
use Log;
use DB;

class OperationUser
{
    private const OK_STATUS = 100;
    private const NG_STATUS = 200;
    private const OTHER_STATUS = 300;
    
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

        $api_client = new ApiClient();
        
        switch ( $api_client->createUser( $data ) ) 
        {
            case self::OK_STATUS:
            
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
                        'exist_externaldb' => 'true',
                        'password' => Hash::make('password'),
                    ]
                );
                break;
            
            case self::OTHER_STATUS:
            
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
                        'exist_externaldb' => 'false',
                        'password' => Hash::make('password'),
                    ]
                );
                break;
            
            default:
                return null;
                
        }

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
        
        $get_user = new GetUser();
        
        if ( !empty( $scim_id ) )
        {
            $users = $get_user->getByScimId( $scim_id );
        }
        else
        {
            $users = $get_user->getByEmail( $data['userName'], $data['tenant_id'] );
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
            if ( $users->exist_externaldb != "false" ) 
            {
                $api_client = new ApiClient();
                if ( $api_client->updateUser( $data ) === false ) 
                {
                    return null;
                }
            }
        }

        $users->save();
        
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
        if ( User::where( 'scim_id', $scim_id )->delete() <= 0 ) 
        {
            return false;
        }
        $users = User::where( 'scim_id', $scim_id )->first();
        $api_client = new ApiClient();
        if ( $api_client->deleteUser( $users ) === false ) 
        {
            return false;
        }
        
        return $return;
    }
}