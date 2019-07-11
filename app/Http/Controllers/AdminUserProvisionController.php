<?php

namespace App\Http\Controllers;

use App\Http\Resources\SCIM\UserResource;
use App\User;
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
    public function index(Request $request)
    {
        $filter = $request->get('filter');
        if ($filter && preg_match('/userName eq (.*)/i', $filter, $matches)) {
            try {
                $users = User::where('email', str_replace('"', '', $matches[1]))->firstOrFail();
                $res_data = $this->createGetReturnData($users);
            } catch (\Exception $e) {
                Log::debug('内部DBにリクエストされたユーザーは見つかりません。');
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
    * [store ユーザーの作成]
    * @param  Request $request 
    * 
    * @return JsonResponse
    */
    public function store(Request $request)
    {
        $data = $request->all();
        if (!isset($data['userName'])) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        if (User::where('email', $data['userName'])->count() > 0) {
            $users = $this->updateUser($data);
        }else{
            $users = $this->createUser($data);
        }
        Log::debug('型');
        Log::debug(gettype($users));
        
        return response()->json($this->createReturnData($users))
        ->setStatusCode(Response::HTTP_CREATED)
        ->header('Content-Type', 'application/scim+json');
    }
    
    /**
    * [show ユーザーの取得]
    * @param  Request $request
    * @param  string  $scim_id [scim_id]
    * 
    * @return JsonResponse
    */
    public function show(Request $request, string $scim_id)
    {
        try {
            $users = User::where('scim_id', $scim_id)->firstOrFail();
        } catch (\Exception $exception) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        
        return response()->json($this->createReturnData($users))
        ->setStatusCode(Response::HTTP_OK)
        ->header('Content-Type', 'application/scim+json');
    }
    
    /**
    * [delete ユーザーの削除]
    * @param  Request $request
    * @param  string  $scim_id [scim_id]
    * 
    * @return JsonResponse
    */
    public function delete(Request $request, string $scim_id)
    {
        if (User::where('scim_id', $scim_id)->delete() < 0) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        
        return response()->setStatusCode(Response::HTTP_NO_CONTENT);
    }
    
    /**
    * [update ユーザーの更新]
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
            $users = User::where('scim_id', $scim_id)->firstOrFail();
            if ($data['Operations']) {
                foreach ($data['Operations'] as $key => $value) {
                    if (!($value['op'] == 'Replace' || $value['op'] == 'Add')) {
                        continue;
                    }
                    if(strpos($value['path'],'email') !== false){
                        $updateDetail['userName'] = $value['value'];
                        continue;
                    }
                    if(strpos($value['path'],'familyName') !== false){
                        $updateDetail['familyName'] = $value['value'];
                        continue;
                    }
                    if(strpos($value['path'],'formatted') !== false){
                        $updateDetail['formatted'] = $value['value'];
                        continue;
                    }
                    if(strpos($value['path'],'givenName') !== false){
                        $updateDetail['givenName'] = $value['value'];
                        continue;
                    }
                    if(strpos($value['path'],'externalId') !== false){
                        $updateDetail['externalId'] = $value['value'];
                        continue;
                    }
                    if(strpos($value['path'],'userName') !== false){
                        $updateDetail['userName'] = $value['value'];
                        continue;
                    }
                }
                $update_users = $this->updateUser($updateDetail,$scim_id);
            }else{
                return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
            }
        } catch (\Exception $exception) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        
        return response()->json($this->createReturnData($update_users))
        ->setStatusCode(Response::HTTP_OK)
        ->header('Content-Type', 'application/scim+json');
    }
    
    /**
    * [createUser ユーザー情報登録]
    * @param  array   $requestData [登録内容]
    * 
    * @return object  $users       [usersテーブルオブジェクト]
    */
    private function createUser(array $requestData){
        Log::debug('ユーザー情報登録内容');
        Log::debug($requestData);
        $scim_id = hash('ripemd160', $requestData['externalId']);
        if (!isset($requestData['name'])) {
            $requestData['name']['givenName'] = '';
            $requestData['name']['familyName'] = '';
            $requestData['name']['formatted'] = '';
        }
        $users = User::create([
            'scim_id' => $scim_id,
            'external_id' => $requestData['externalId'],
            'given_name' => $requestData['name']['givenName'],
            'family_name' => $requestData['name']['familyName'],
            'user_name' => $requestData['name']['formatted'],
            'email' => $requestData['userName'],
            'active' => $requestData['active'],
            'password' => Hash::make('password'),
        ]);
        Log::debug('ユーザー情報登録完了');
        return $users;
    }
    
    /**
    * [updateUser ユーザー情報更新]
    * @param  array   $requestData [更新内容]
    * @param  string|null $scim_id [scim_id]
    * 
    * @return object $users        [usersテーブルオブジェクト]
    */
    private function updateUser(array $requestData, ?string $scim_id = null)
    {
        Log::debug('ユーザー情報更新内容');
        Log::debug($requestData);
        try {
            if (!empty($scim_id)) {
                $users = User::where('scim_id', $scim_id)->firstOrFail();
            }else{
                if (isset($requestData['userName'])) {
                    $users = User::where('email', $requestData['userName'])->firstOrFail();
                }else{
                    return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
                }
            }
        } catch (\Exception $exception) {
            return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
        }
        if (isset($requestData['active'])) {
            $users->active = $requestData['active'];
        }
        if (isset($requestData['formatted'])) {
            $users->user_name = $requestData['formatted'];
        } 
        if (isset($requestData['givenName'])) {
            $users->given_name = $requestData['givenName'];
        } 
        if (isset($requestData['familyName'])) {
            $users->family_name = $requestData['familyName'];
        } 
        if (isset($requestData['externalId'])) {
            $users->external_id = $requestData['externalId'];
        } 
        if (isset($requestData['userName'])) {
            $users->email = $requestData['userName'];
        } 
        $users->save();
        Log::debug('ユーザー情報更新完了');
        return $users;
    }
    
    /**
    * Returns a SCIM-formatted error message
    *
    * @param string|null $message
    * @param int $statusCode
    *
    * @return JsonResponse
    */
    private function scimError(?string $message = null, int $statusCode): JsonResponse
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
    * @param  object|null $users [usersテーブルオブジェクト]
    * 
    * @return array $return
    */
    private function createGetReturnData(?object $users = null)
    {
        $return = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'startIndex' => 1,
            'itemsPerPage' => 20
        ];
        if (isset($users) && $users->count() > 0) {
            $return['totalResults'] = $users->count();
            $return['Resources'] = [
                'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
                'id' => $users->scim_id,
                'externalId' => $users->external_id,
                'meta' => [
                    'resourceType' => 'User',
                    'created' => $users->created_at->toIso8601String(),
                    'lastModified' => $users->updated_at->toIso8601String(),
                ],
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
        }else{
            $return['totalResults'] = 0;
        }
        Log::debug($return);
        return $return;
    }
    
    /**
    * [createReturnData レスポンスデータ作成]
    *
    * @param  object $users [usersテーブルオブジェクト]
    *
    * @return array $return
    */
    private function createReturnData(object $users)
    {
        $location = getenv('LOCATION_URL').'/Users/'.$users->scim_id;
        $return = [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'id' => $users->scim_id,
            'externalId' => $users->external_id,
            'meta' => [
                'resourceType' => 'User',
                'created' => $users->created_at->toIso8601String(),
                'lastModified' => $users->updated_at->toIso8601String(),
                'location' => $location
            ],
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