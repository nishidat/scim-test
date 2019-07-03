<?php

namespace App\Http\Controllers;

use App\Http\Resources\SCIM\UserResource;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Log;

class AdminGroupProvisionController extends Controller
{  
  /**
   * [store description]
   * @param  Request $request [description]
   * @return [type]           [description]
   */
  public function store(Request $request)
  {
    Log::debug('============Request Group POST start=============');
    Log::debug($request->all());
    Log::debug('============Request Group POST end=============');
    return response()->setStatusCode(Response::HTTP_CREATED);
  }
  
  public function show(Request $request, string $scim_id)
  {
    Log::debug('============Request Group GET start=============');
    Log::debug($request->all());
    Log::debug('============Request Group GET end=============');
    
    return response()->setStatusCode(Response::HTTP_OK);
  }
  
  public function delete(Request $request, string $email)
  {
    Log::debug('============Request Group DELETE start=============');
    Log::debug($request->all());
    Log::debug('============Request Group DELETE end=============');
    
    return response()->setStatusCode(Response::HTTP_OK);
  }
  
  public function update(Request $request, string $scim_id)
  {
    Log::debug('============Request Group PATCH start=============');
    Log::debug($request->all());
    Log::debug('============Request Group PATCH end=============');
    
    return response()->setStatusCode(Response::HTTP_OK);
  }
  
  /**
   * Update user data
   * @param array $requestData
   */
  private function updateUser(array $requestData, string $scim_id = null)
  {
    if ($scim_id) {
      $user = User::where('scim_id', $scim_id)->firstOrFail();
    }else{
      $user = User::where('email', $requestData['userName'])->firstOrFail();
    }
    if (isset($requestData['active'])) {
      $user->active = $requestData['active'];
    }
    if (isset($requestData['formatted'])) {
      $user->user_name = $requestData['formatted'];
    } 
    if (isset($requestData['givenName'])) {
      $user->given_name = $requestData['givenName'];
    } 
    if (isset($requestData['familyName'])) {
      $user->family_name = $requestData['familyName'];
    } 
    if (isset($requestData['externalId'])) {
      $user->external_id = $requestData['externalId'];
    } 
    if (isset($requestData['userName'])) {
      $user->email = $requestData['userName'];
    } 
    $user->save();
    
    Log::debug('ユーザー更新');
    
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
    $location = 'https://scim-proxy.azurewebsites.net/api/scim/v2/Users/'.$user->scim_id;
    
    Log::debug('============Response start=============');
    Log::debug(
      response()->json(
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
    );
    Log::debug('============Response end=============');
    
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
      ->header('Content-Type', 'application/scim+json');
      // ->header('Location', $location);
  }
}