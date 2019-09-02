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
                        'active' => 'true',
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
                        'active' => 'true',
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
        $delete_api_flag = false;
        
        if ( !empty( $scim_id ) )
        {
            $users = $get_user->getByScimId( $scim_id, $data['tenant_id'] );
        }
        else
        {
            $users = $get_user->getByEmail( $data['userName'], $data['tenant_id'] );
        }
        $data['olduserName'] = $users->email;
        if ( isset( $data['active'] ) )
        {
            if ( $data['active'] == 'true' ) 
            {
                $users->active = 'true';
            }
            else
            {
                $users->active = 'false';
            }
            
            if ( $users->exist_externaldb != 'false' ) 
            {
                if ( $users->active == 'true' ) 
                {
                    $api_client = new ApiClient();
                    if ( $api_client->createUser( $data ) == self::NG_STATUS ) 
                    {
                        return null;
                    }
                }
                else
                {
                    $api_client = new ApiClient();
                    if ( $api_client->deleteUser( $users ) === false ) 
                    {
                        return null;
                    }
                    $delete_api_flag = true;
                }
            }
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
            if ( $users->exist_externaldb != 'false' && $delete_api_flag === false ) 
            {
                $api_client = new ApiClient();
                if ( $api_client->updateUser( $data ) === false ) 
                {
                    return null;
                }
            }
        }
        if ( isset( $data['groupId'] ) )
        {
            $users->group_id = $data['groupId'];
            if ($data['groupId'] == 'remove') {
                $users->group_id = null;
            }
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
            if ( $users->exist_externaldb != 'false' && $delete_api_flag === false ) 
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
        
        $get_user = new GetUser();
        $users = $get_user->getByScimId( $scim_id, $tenant_id );
        if ( $users->exist_externaldb != "false" ) 
        {
            $api_client = new ApiClient();
            if ( $api_client->deleteUser( $users ) === false ) 
            {
                return false;
            }
        }
        if ( User::where( 'scim_id', $scim_id )->where( 'tenant_id', $tenant_id )->delete() <= 0 ) 
        {
            return false;
        }
        
        return $return;
    }
}