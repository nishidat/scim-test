<?php

namespace App\Http\Controllers;

use App\Http\Resources\SCIM\UserResource;
//use Illuminate\Foundation\Auth\User;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Log;

class AdminUserProvisionController extends Controller
{
  /**
  * Display a listing of the resource.
  *
  * @throws \Exception
  */
  public function index(Request $request)
  {
    $filter = $request->get('filter');
    
    Log::debug('============Request Users GET start=============');
    Log::debug($request->all());
    Log::debug('============Request Users GET end=============');
    
    if ($filter && preg_match('/userName eq (.*)/i', $filter, $matches)) {
      try {
        $users = User::where('email', str_replace('"', '', $matches[1]))->firstOrFail();
        Log::debug('============email start=============');
        Log::debug($filter);
        Log::debug($matches[1]);
        Log::debug($users);
        Log::debug('============email end=============');
      } catch (\Exception $e) {
        $return = [
          'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
          'totalResults' => 0,
          'startIndex' => 1,
          'itemsPerPage' => 20,
          'Resources' => []
        ];
        return response()->json($return)->setStatusCode(Response::HTTP_OK);
      }
    }else{
      $return = [
        'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
        'totalResults' => 0,
        'startIndex' => 1,
        'itemsPerPage' => 20,
        'Resources' => []
      ];
      return response()->json($return)->setStatusCode(Response::HTTP_OK);
    }
    
    $return = [
      'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
      'totalResults' => $users->count(),
      'startIndex' => 1,
      'itemsPerPage' => 20
    ];
    
    if ($users->count()) {
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
      $return['Resources'] = [];
    }
    
    Log::debug('============Response Users GET start=============');
    Log::debug($return);
    Log::debug('============Response Users GET end=============');
    
    return response()->json($return)->setStatusCode(Response::HTTP_OK);
  }
  
  /**
  * Show the form for creating a new resource.
  *
  * @throws \Exception
  */
  public function create()
  {
    throw new \Exception('Not implemented');
  }
  
  public function store(Request $request)
  {
    $data = $request->all();
    
    Log::debug('============Request Users POST start=============');
    Log::debug($request->all());
    Log::debug('============Request Users POST end=============');
    
    if (User::where('email', $data['userName'])->count()) {
      $this->updateUser($data);
    }else{
      $user = User::create([
        'scim_id' => hash('sha128', $data['externalId']),
        'external_id' => $data['externalId'],
        'given_name' => $data['name']['givenName'],
        'family_name' => $data['name']['familyName'],
        'user_name' => $data['name']['formatted'],
        'email' => $data['userName'],
        'active' => $data['active'],
        'password' => Hash::make('password'),
      ]);
    }
  
    Log::debug('============Response Users POST start=============');
    Log::debug('ユーザー作成');
    Log::debug('============Response Users POST end=============');
    
    return $this->responseUserData($data['userName'], Response::HTTP_CREATED);
  }
  
  public function show(Request $request, string $scim_id)
  {
    Log::debug('============Request Users/{email} GET start=============');
    Log::debug($request->all());
    Log::debug('============Request Users/{email} GET end=============');
    
    try {
      $user = User::where('scim_id', $scim_id)->firstOrFail();
    } catch (\Exception $exception) {
      return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
    }
    
    Log::debug('============Response Users/{email} GET start=============');
    Log::debug('ユーザー取得');
    Log::debug('============Response Users/{email} GET end=============');
    
    return $this->responseUserData($scim_id, Response::HTTP_OK);
  }
  
  public function delete(Request $request, string $email)
  {
    Log::debug('============Request Users/{email} DELETE start=============');
    Log::debug($request->all());
    Log::debug('============Request Users/{email} DELETE end=============');
    
    try {
      User::where($email)->delete();
    } catch (\Exception $exception) {
      return $this->scimError('User does not exist', Response::HTTP_NOT_FOUND);
    }
    
    Log::debug('============Response Users/{email} DELETE start=============');
    Log::debug('ユーザー取得');
    Log::debug('============Response Users/{email} DELETE end=============');
    
    return response()->setStatusCode(Response::HTTP_NO_CONTENT);
  }
  
  public function update(Request $request, string $email)
  {
    $data = $request->all();
    
    Log::debug('============Request Users/{email} PATCH start=============');
    Log::debug($request->all());
    Log::debug('============Request Users/{email} PATCH end=============');
    
    $updateDetail = array();
    
    if ($data['Operations']) {
      foreach ($data['Operations'] as $key => $value) {
        if ($value['op'] != 'Replace') {
          continue;
        }
        if(strpos($value['path'],'email') !== false){
          $updateDetail['userName'] = $value['value'];
          continue;
        }
        if(strpos($value['path'],'familyName') !== false){
          $updateDetail['name']['familyName'] = $value['value'];
          continue;
        }
        if(strpos($value['path'],'formatted') !== false){
          $updateDetail['name']['formatted'] = $value['value'];
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
      $this->updateUser($updateDetail);
    }
    
    return $this->responseUserData($email, Response::HTTP_OK);
  }
  
  /**
   * Update user data
   * @param array $requestData
   */
  private function updateUser(array $requestData)
  {
    $user = User::where('email', $requestData['userName'])->firstOrFail();   
    if ($requestData['active']) {
      $user->active = $requestData['active'];
    }
    if ($requestData['name']['formatted']) {
      $user->user_name = $requestData['name']['formatted'];
    } 
    if ($requestData['name']['givenName']) {
      $user->given_name = $requestData['name']['givenName'];
    } 
    if ($requestData['name']['familyName']) {
      $user->family_name = $requestData['name']['familyName'];
    } 
    if ($requestData['externalId']) {
      $user->external_id = $requestData['externalId'];
    } 
    if ($requestData['userName']) {
      $user->email = $requestData['userName'];
    } 
    $user->save();
    
    Log::debug('============Response Users/{email} PATCH start=============');
    Log::debug('ユーザー更新');
    Log::debug('============Response Users/{email} PATCH end=============');
    
  }
  
  /**
  * Returns a SCIM-formatted error message
  *
  * @param string|null $message
  * @param int $statusCode
  *
  * @return JsonResponse
  */
  protected function scimError(?string $message, int $statusCode): JsonResponse
  {
    return response()->json(
      [
        'schemas' => ["urn:ietf:params:scim:api:messages:2.0:Error"],
        'detail' => $message ?? 'An error occured',
        'status' => $statusCode,
      ])->setStatusCode($statusCode);
  }
  
  /**
  * Returns Create User message
  *
  * @param string $email
  *
  * @return JsonResponse
  */
  private function responseUserData(string $scim_id, int $statusCode): JsonResponse
  {
    $user = User::where('scim_id', $scim_id)->firstOrFail();
    $location = 'https://mmr-commander-staging-scim-devlop.azurewebsites.net/api/scim/v2/Users/'.$user->scim_id;
    return response()->json(
      [
        'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
        'id' => $user->scim_id,
        'externalId' => $user->external_id,
        'meta' => [
            'resourceType' => 'User',
            'created' => $user->created_at->toIso8601String(),
            'lastModified' => $user->updated_at->toIso8601String(),
            'location' => $location
        ],
        'userName' => $user->email,
        'name' => [
            'formatted' => $user->user_name,
            'givenName' => $user->given_name,
            'familyName' => $user->family_name,
        ],
        'active' => $user->active,
        'emails' => [
          'value' => $user->email,
          'type' => 'work',
          'primary' => 'true'
        ]
      ])
      ->setStatusCode($statusCode)
      ->header('Content-Type', 'application/scim+json')
      ->header('Location', $location);
  }
}