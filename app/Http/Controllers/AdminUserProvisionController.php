<?php

namespace App\Http\Controllers;

use App\User\OperationUser;
use App\User\GetUser;
use App\Model\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Log;

class AdminUserProvisionController extends Controller
{
    /**
    * [index クエリによるユーザーの取得]
    * @param  Request $request
    * 
    * @return JsonResponse
    */
    public function index( Request $request )
    {
        if ( !$request->filled( 'filter' ) ) 
        {
            return $this->scimError( 'filter がリクエストされていません。' );
        }
        $filter = $request->get( 'filter' );
        
        if ( !preg_match( '/userName eq (.*)/i', $filter, $matches ) )
        {
            return $this->scimError( 'filter の形式が正しくありません。' );
        }
        $email = str_replace( '"', '', $matches[1] );
        
        $get_user = new GetUser();
        $users_object = $get_user->getByEmail( $email );
        if( $users_object === null ) 
        {
            $res_data = $this->createGetReturnData();
        }
        else
        {
            $res_data = $this->createGetReturnData( $users_object );
        }
        
        return response()
            ->json( $res_data, Response::HTTP_OK )
            ->header( 'Content-Type', 'application/scim+json' );
    }
    
    /**
    * [store ユーザーの作成]
    * @param  Request $request 
    * 
    * @return JsonResponse
    */
    public function store( Request $request, string $tenant_id )
    {
        $data = $request->all();
        if ( !isset( $data['userName'] ) ) 
        {
            return $this->scimError( 'userName がリクエストされていません。' );
        }
        if ( !isset( $tenant_id ) ) 
        {
            return $this->scimError( 'tenant_id がリクエストされていません。' );
        }
        $data['tenant_id'] = $tenant_id;
        $get_user = new GetUser();
        $operation_user = new OperationUser();
        $users_object = $get_user->getByEmail( $data['userName'] );
        if( $users_object === null ) 
        {
            $users_new_object = $operation_user->create( $data );
            if ( $users_new_object === null ) 
            {
                return $this->scimError( 'ユーザーの作成に失敗しました。' );
            }
        }
        else
        {
            $users_new_object = $users_object;
        }
        
        return response()
            ->json( $this->createReturnData( $users_new_object ), Response::HTTP_CREATED )
            ->header( 'Content-Type', 'application/scim+json' );
    }
    
    /**
    * [show ユーザーの取得]
    * @param  Request $request
    * @param  string  $scim_id [scim_id]
    * 
    * @return JsonResponse
    */
    public function show( Request $request, string $tenant_id, string $scim_id )
    {
        $get_user = new GetUser();
        $users_object = $get_user->getByScimId( $scim_id );
        if( $users_object === null ) 
        {
            return $this->scimError( 'リクエストされた scim_id（User） は、存在しません。' );
        }

        return response()
            ->json( $this->createReturnData( $users_object ), Response::HTTP_OK )
            ->header( 'Content-Type', 'application/scim+json' );
    }
    
    /**
    * [delete ユーザーの削除]
    * @param  Request $request
    * @param  string  $scim_id [scim_id]
    * 
    * @return JsonResponse
    */
    public function delete( Request $request, string $tenant_id, string $scim_id )
    {
        $operation_user = new OperationUser();
        if ( !$operation_user->deleteUserByScimId( $scim_id, $tenant_id ) )
        {
            return $this->scimError( 'リクエストされた scim_id（User） は、存在しません。' );
        }
        
        return response( 'No body', Response::HTTP_NO_CONTENT );
    }
    
    /**
    * [update ユーザーの更新]
    * @param  Request $request
    * @param  string  $scim_id [scim_id]
    * 
    * @return JsonResponse
    */
    public function update( Request $request, string $tenant_id, string $scim_id )
    {
        $data = $request->all();
        $update_detail = array();
        if ( !isset( $data['Operations'] ) )
        {
            return $this->scimError( 'Operations がリクエストされていません。' );
        }
        $update_detail['tenant_id'] = $tenant_id;
        $get_user = new GetUser();
        $users_object = $get_user->getByScimId( $scim_id );
        if( $users_object === null ) 
        {
            return $this->scimError( 'リクエストされた scim_id（User） は、存在しません。' );
        }

        foreach ( $data['Operations'] as $key => $value ) 
        {
            if ( !( $value['op'] == 'Replace' || $value['op'] == 'Add' ) ) 
            {
                continue;
            }
            if( strpos( $value['path'], 'email') !== false )
            {
                $update_detail['userName'] = $value['value'];
                continue;
            }
            if( strpos( $value['path'], 'familyName') !== false )
            {
                $update_detail['familyName'] = $value['value'];
                continue;
            }
            if( strpos( $value['path'], 'formatted') !== false )
            {
                $update_detail['formatted'] = $value['value'];
                continue;
            }
            if( strpos( $value['path'], 'givenName') !== false )
            {
                $update_detail['givenName'] = $value['value'];
                continue;
            }
            if( strpos( $value['path'], 'displayName') !== false )
            {
                $update_detail['displayName'] = $value['value'];
                continue;
            }
            if( strpos( $value['path'], 'externalId') !== false )
            {
                $update_detail['externalId'] = $value['value'];
                continue;
            }
            if( strpos( $value['path'], 'userName') !== false )
            {
                
                $update_detail['userName'] = $value['value'];
                continue;
            }
            if( strpos( $value['path'], 'active') !== false )
            {
                
                $update_detail['active'] = $value['value'];
                continue;
            }
        }
        $operation_user = new OperationUser();
        $users_new_object = $operation_user->update( $update_detail, $scim_id );
        if ( $users_new_object === null ) 
        {
            return $this->scimError( '更新処理に失敗しました。' );
        }
        return response()
            ->json( $this->createReturnData( $users_new_object ), Response::HTTP_OK )
            ->header( 'Content-Type', 'application/scim+json' );
    }
    
    /**
    * [scimError エラーレスポンス]
    * @param string|null $message
    *
    * @return JsonResponse
    */
    private function scimError(?string $message = null): JsonResponse
    {
        $return = 
        [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
            'detail' => $message ?? 'An error occured',
            'status' => Response::HTTP_NOT_FOUND,    
        ];
        Log::debug( '============Response Start============' );
        Log::debug( $return );
        Log::debug( '============Response End============' );
        
        return response()->json( $return, Response::HTTP_NOT_FOUND );
    }
        
    /**
    * [createGetReturnData GETリクエスト用レスポンスデータ作成]
    * @param  User|null $users [usersテーブルオブジェクト]
    * 
    * @return array     $return
    */
    private function createGetReturnData( ?User $users = null ): array
    {
        $return = 
        [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'startIndex' => 1,
            'itemsPerPage' => 20
        ];
        if ( isset( $users ) ) 
        {
            $return['totalResults'] = 1;
            $return['Resources'] = 
            [
                'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
                'id' => $users->scim_id,
                'externalId' => $users->external_id,
                'meta' => [
                    'resourceType' => 'User',
                    'created' => $users->created_at->toIso8601String(),
                    'lastModified' => $users->updated_at->toIso8601String(),
                ],
                'displayName' => $users->display_name,
                'userName' => $users->email,
                'name' => [
                    'formatted' => $users->user_name,
                    'givenName' => $users->givenName,
                    'familyName' => $users->familyName,
                ],
                'active' => $users->active,
                'emails' => [
                    'value' => $users->email,
                    'type' => 'work',
                    'primary' => 'true'
                ]
            ];
        }
        else
        {
            $return['totalResults'] = 0;
        }
        Log::debug($return);
        
        return $return;
    }
    
    /**
    * [createReturnData レスポンスデータ作成]
    *
    * @param  User  $users [usersテーブルオブジェクト]
    *
    * @return array $return
    */
    private function createReturnData( User $users ): array
    {
        $location = getenv( 'LOCATION_URL' ) . '/Users/' . $users->scim_id;
        $return = 
        [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'id' => $users->scim_id,
            'externalId' => $users->external_id,
            'meta' => [
                'resourceType' => 'User',
                'created' => $users->created_at->toIso8601String(),
                'lastModified' => $users->updated_at->toIso8601String(),
                'location' => $location
            ],
            'displayName' => $users->display_name,
            'userName' => $users->email,
            'name' => [
                'formatted' => $users->user_name,
                'givenName' => $users->given_name,
                'familyName' => $users->family_name,
            ],
            'active' => $users->active,
            'emails' => [
                'value' => $users->email,
                'type' => 'work',
                'primary' => 'true'
            ]
        ];
        Log::debug($return);
        
        return $return;
    }
}