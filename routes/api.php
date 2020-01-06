<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('scim/v2')
->middleware('requestlog')
->group(function (\Illuminate\Routing\Router $router) {
    // Users
    $router->get('{tenant_id}/Users', 'AdminUserProvisionController@index')
    ->name('api.user.index');
    
    $router->get('{tenant_id}/Users/{scim_id}', 'AdminUserProvisionController@show')
    ->name('api.user.get');
    
    $router->post('{tenant_id}/Users', 'AdminUserProvisionController@store')
    ->name('api.user.create');
    
    $router->put('{tenant_id}/Users/{scim_id}', 'AdminUserProvisionController@replace')
    ->name('api.user.replace');
    
    $router->patch('{tenant_id}/Users/{scim_id}', 'AdminUserProvisionController@update')
    ->name('api.user.update');
    
    $router->delete('{tenant_id}/Users/{scim_id}', 'AdminUserProvisionController@delete')
    ->name('api.user.delete');
    
    // Group
    $router->get('{tenant_id}/Groups', 'AdminGroupProvisionController@index')
    ->name('api.group.index');
    
    $router->post('{tenant_id}/Groups', 'AdminGroupProvisionController@store')
    ->name('api.group.create');
    
    $router->get('{tenant_id}/Groups/{scim_id}', 'AdminGroupProvisionController@show')
    ->name('api.group.show');
    
    $router->patch('{tenant_id}/Groups/{scim_id}', 'AdminGroupProvisionController@update')
    ->name('api.group.update');
    
    $router->delete('{tenant_id}/Groups/{scim_id}', 'AdminGroupProvisionController@delete')
    ->name('api.group.delete');
});
