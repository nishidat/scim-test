<?php

namespace App\Http\Controllers;

use App\User;
use App\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
    public function index(Request $request)
    {
        $filter = $request->get('filter');
        if ($filter && preg_match('/displayName eq (.*)/i', $filter, $matches)) {
            try {
                $groups = Group::where('group_name', str_replace('"', '', $matches[1]))->firstOrFail();
                $res_data = $this->createGetReturnData($groups);
            } catch (\Exception $e) {
                Log::debug('内部DBにリクエストされたグループは見つかりません。');
                $res_data = $this->createGetReturnData();
            }
        }else{
            // ヘルスチェックのため、正常ステータスで返却する
            Log::debug('filterがリクエストされていません。');
            $res_data = $this->createGetReturnData();
        }
        
        return response()->json($res_data)
        ->setStatusCode(Response::HTTP_OK)
        ->header('Content-Type', 'application/scim+json');
    }
    
    /**
    * [store グループの作成]
    * @param  Request $request
    * 
    * @return JsonResponse
    */
    public function store(Request $request)
    {
        $data = $request->all();
        if (!isset($data['displayName'])) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        if (Group::where('group_name', $data['displayName'])->count() > 0) {
            $groups = $this->updateGroup($data);
        }else{
            $groups = $this->createGroup($data);
        }
        
        return response()->json($this->createReturnData($groups))
        ->setStatusCode(Response::HTTP_CREATED)
        ->header('Content-Type', 'application/scim+json');
    }
    
    /**
    * [show グループの取得]
    * @param  Request $request
    * 
    * @return JsonResponse
    */
    public function show(Request $request, string $scim_id)
    {
        try {
            $groups = Group::where('scim_id', $scim_id)->firstOrFail();
        } catch (\Exception $exception) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        
        return response()->json($this->createReturnData($groups))
        ->setStatusCode(Response::HTTP_OK)
        ->header('Content-Type', 'application/scim+json');
    }
    
    /**
    * [delete グループの削除]
    * @param  Request $request
    * 
    * @return JsonResponse
    */
    public function delete(Request $request, string $email)
    {
        if (Group::where('scim_id', $scim_id)->delete() < 0) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        
        return response()->setStatusCode(Response::HTTP_NO_CONTENT);
    }
    
    /**
    * [update グループの更新]
    * @param  Request $request
    * @param  string  $scim_id [scim_id]
    * 
    * @return JsonResponse
    */
    public function update(Request $request, string $scim_id)
    {
        $data = $request->all();
        $updateDetail = array();
        if (!isset($data['Operations'])) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        try {
            if ($data['Operations']) {
                foreach ($data['Operations'] as $key => $value) {
                    switch ($value['op']) {
                        case 'Replace':
                            if(strpos($value['path'],'displayName') !== false){
                                $updateDetail['displayName'] = $value['value'];
                                $this->updateGroup($updateDetail,$scim_id);
                            }
                            break;
                            
                        case 'Add':
                            if(strpos($value['path'],'members') !== false){
                                try {
                                    $groups = Group::where('scim_id', $scim_id)->firstOrFail();
                                } catch (\Exception $exception) {
                                    return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
                                }
                                $users = User::where('email', $value['value']['value']);
                                if (isset($users)) {
                                    $users->group_id = $groups->id;
                                }else{
                                    try {
                                        $users = User::where('scim_id', $value['value']['value'])->firstOrFail();
                                    } catch (\Exception $exception) {
                                        return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
                                    }
                                    $users->group_id = $groups->id;
                                }
                                $users->save();
                            }
                            break;
                            
                        case 'Remove':
                            if(strpos($value['path'],'members') !== false){
                                try {
                                    $groups = Group::where('scim_id', $scim_id)->firstOrFail();
                                } catch (\Exception $exception) {
                                    return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
                                }
                                $users = User::where('email', $value['value']['value']);
                                if (isset($users)) {
                                    $users->group_id = "";
                                }else{
                                    try {
                                        $users = User::where('scim_id', $value['value']['value'])->firstOrFail();
                                    } catch (\Exception $exception) {
                                        return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
                                    }
                                    $users->group_id = "";
                                }
                                $users->save();
                            }
                            break;
                        
                        default:
                            continue;
                    }
                    continue;
                }
            }else{
                return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
            }
        } catch (\Exception $exception) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        
        return response()->setStatusCode(Response::HTTP_NO_CONTENT);
    }
    /**
    * [createGroup グループ情報登録]
    * @param  array   $requestData [登録内容]
    * 
    * @return object $groups        [groupsテーブルオブジェクト]
    */
    private function createGroup(array $requestData){
        Log::debug('グループ情報登録内容');
        Log::debug($requestData);
        $scim_id = hash('ripemd160', $requestData['externalId']);
        $groups = Group::create([
            'scim_id' => $scim_id,
            'external_id' => $requestData['externalId'],
            'group_name' => $requestData['displayName'],
        ]);
        Log::debug('グループ情報登録完了');
        return $groups;
    }
    
    /**
    * [updateGroup グループ情報更新]
    * @param  array   $requestData [更新内容]
    * @param  string|null $scim_id [scim_id]
    * 
    * @return object $groups        [groupsテーブルオブジェクト]
    */
    private function updateGroup(array $requestData, ?string $scim_id)
    {
        Log::debug('グループ情報更新内容');
        Log::debug($requestData);
        try {
            if (!empty($scim_id)) {
                $groups = Group::where('scim_id', $scim_id)->firstOrFail();
            }else{
                if (isset($requestData['displayName'])) {
                    $groups = Group::where('group_name', $requestData['displayName'])->firstOrFail();
                }else{
                    return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
                }
            }
        } catch (\Exception $exception) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        if (isset($requestData['displayName'])) {
            $groups->group_name = $requestData['displayName'];
        }
        $groups->save();
        Log::debug('グループ情報更新完了');
        return $groups;
    }
    
    /**
    * Returns a SCIM-formatted error message
    *
    * @param string|null $message
    * @param int $statusCode
    *
    * @return JsonResponse
    */
    private function scimError(?string $message, int $statusCode): JsonResponse
    {
        return response()->json(
            [
                'schemas' => ["urn:ietf:params:scim:api:messages:2.0:Error"],
                'detail' => $message ?? 'An error occured',
                'status' => $statusCode,    
            ])
            ->setStatusCode($statusCode);
    }
    
    /**
    * [createGetReturnData GETリクエスト用レスポンスデータ作成]
    * @param  object|null $groups [groupsテーブルオブジェクト]
    * 
    * @return array $return
    */
    private function createGetReturnData(?object $groups)
    {
        $return = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'startIndex' => 1,
            'itemsPerPage' => 20
        ];
        if (isset($groups) && $groups->count() > 0) {
            $return['totalResults'] = $groups->count();
        }else{
            $return['totalResults'] = 0;
        }
        Log::debug($return);
        return $return;
    }
    
    /**
    * [createReturnData レスポンスデータ作成]
    *
    * @param  object $groups [groupsテーブルオブジェクト]
    *
    * @return array $return
    */
    private function createReturnData(object $groups)
    {
        $location = getenv('LOCATION_URL').'/Groups/'.$groups->scim_id;
        $return = [
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
        Log::debug($return);
        return $return;
    }
}