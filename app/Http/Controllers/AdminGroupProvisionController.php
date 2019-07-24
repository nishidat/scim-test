<?php

namespace App\Http\Controllers;

use App\User\OperationUser;
use App\Group\OperationGroup;
use App\Group\GetGroup;
use App\Model\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Log;

class AdminGroupProvisionController extends Controller
{
    /**
    * [index displayNameでのグループの取得]
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
        
        if ( !preg_match( '/displayName eq (.*)/i', $filter, $matches ) )
        {
            return $this->scimError( 'filter の形式が正しくありません。' );
        }
        $group_name = str_replace( '"', '', $matches[1] );
        
        $get_group = new GetGroup();
        $groups_object = $get_group->getByGroupName( $group_name );
        if( $groups_object === null )
        {
            $res_data = $this->createGetReturnData();
        }
        else
        {
            $res_data = $this->createGetReturnData( $groups_object );
        }
        
        return response()
            ->json( $res_data, Response::HTTP_OK )
            ->header( 'Content-Type', 'application/scim+json' );
    }
    
    /**
    * [store グループの作成]
    * @param  Request $request
    * 
    * @return JsonResponse
    */
    public function store( Request $request )
    {
        $data = $request->all();
        if ( !isset( $data['displayName'] ) ) 
        {
            return $this->scimError( 'displayName がリクエストされていません。' );
        }
        
        $get_group = new GetGroup();
        $operation_group = new OperationGroup();
        $groups_object = $get_group->getByGroupName( $data['displayName'] );
        if( $groups_object === null ) 
        {
            $groups_new_object = $operation_group->update( $data );
        }
        else
        {
            $groups_new_object = $operation_group->create( $data );
        }
        
        return response()
            ->json( $this->createReturnData( $groups_new_object ), Response::HTTP_CREATED )
            ->header( 'Content-Type', 'application/scim+json' );
    }
    
    /**
    * [show グループの取得]
    * @param  Request $request
    * 
    * @return JsonResponse
    */
    public function show( Request $request, string $scim_id )
    {
        $get_group = new GetGroup();
        $groups_object = $get_group->getByScimId( $scim_id );
        if( $groups_object === null ) 
        {
            return $this->scimError( 'リクエストされた scim_id（Group） は、存在しません。' );
        }
        
        return response()
            ->json( $this->createReturnData( $groups_object ), Response::HTTP_OK )
            ->header( 'Content-Type', 'application/scim+json' );
    }
    
    /**
    * [delete グループの削除]
    * @param  Request $request
    * 
    * @return JsonResponse
    */
    public function delete( Request $request, string $scim_id )
    {
        $operation_group = new OperationGroup();
        if ( !$operation_group->deleteUserByScimId( $scim_id ) )
        {
            return $this->scimError( 'リクエストされた scim_id（Group） は、存在しません。' );
        }
        
        return response( 'No body', Response::HTTP_NO_CONTENT );
    }
    
    /**
    * [update グループの更新]
    * @param  Request $request
    * @param  string  $scim_id [scim_id]
    * 
    * @return JsonResponse
    */
    public function update( Request $request, string $scim_id )
    {
        $data = $request->all();
        $update_detail = array();
        if ( !isset( $data['Operations'] ) ) 
        {
            return $this->scimError( 'Operations がリクエストされていません。' );
        }
        
        $get_group = new GetGroup();
        $groups_object = $get_group->getByScimId( $scim_id );
        if( $groups_object === null ) 
        {
            return $this->scimError( 'リクエストされた scim_id（Group） は、存在しません。' );
        }
        
        foreach ( $data['Operations'] as $key => $value ) 
        {
            switch ( $value['op'] ) 
            {
                case 'Replace':
                    if( strpos( $value['path'],'displayName' ) !== false )
                    {
                        $update_detail['displayName'] = $value['value'];
                        $operation_group = new OperationGroup();
                        $operation_group->update( $data, $scim_id );
                    }
                    break;
                    
                case 'Add':
                    if( strpos( $value['path'], 'members' ) !== false )
                    {
                        $update_detail['groupId'] = $groups_object->id;
                        $operation_user = new OperationUser();
                        $operation_user->update( $update_detail, $value['value'][0]['value'] );
                    }
                    break;
                    
                case 'Remove':
                    if( strpos( $value['path'], 'members' ) !== false )
                    {
                        $update_detail['groupId'] = null;
                        $operation_user = new OperationUser();
                        $operation_user->update( $update_detail, $value['value'][0]['value'] );
                    }
                    break;
                
                default:
                    break;
            }
            continue;
        }
        
        return response( 'No body', Response::HTTP_NO_CONTENT );
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
            'schemas' => ["urn:ietf:params:scim:api:messages:2.0:Error"],
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
    * @param  Group|null $groups [groupsテーブルオブジェクト]
    * 
    * @return array      $return
    */
    private function createGetReturnData( ?Group $groups = null ): array
    {
        $return = 
        [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'startIndex' => 1,
            'itemsPerPage' => 20
        ];
        if ( isset( $groups ) && $groups->count() > 0) 
        {
            $return['totalResults'] = $groups->count();
        }else{
            $return['totalResults'] = 0;
        }
        Log::debug('============Response Start============');
        Log::debug($return);
        Log::debug('============Response End============');
        
        return $return;
    }
    
    /**
    * [createReturnData レスポンスデータ作成]
    *
    * @param  Group $groups [groupsテーブルオブジェクト]
    *
    * @return array $return
    */
    private function createReturnData( Group $groups ): array
    {
        $location = getenv( 'LOCATION_URL' ) . '/Groups/' . $groups->scim_id;
        $return = 
        [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'id' => $groups->scim_id,
            'externalId' => $groups->external_id,
            'meta' => [
                'resourceType' => 'Group',
                'created' => $groups->created_at->toIso8601String(),
                'lastModified' => $groups->updated_at->toIso8601String(),
                'location' => $location
            ],
            'displayName' => $groups->group_name,
        ];
        Log::debug('============Response Start============');
        Log::debug($return);
        Log::debug('============Response End============');
        
        return $return;
    }
}