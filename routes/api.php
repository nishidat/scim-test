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
    ->middleware('client')
    ->group(function (\Illuminate\Routing\Router $router) {
        $router->get('Users', 'AdminUserProvisionController@index')
            ->name('api.user.index');

        $router->get('Users/{email}', 'AdminUserProvisionController@show')
            ->name('api.user.get');

        $router->post('Users', 'AdminUserProvisionController@store')
            ->name('api.user.create');

//        $router->put('Users/{email}', 'AdminUserProvisionController@replace')
//            ->name('api.user.replace');

//        $router->patch('Users/{email}', 'AdminUserProvisionController@update')
//            ->name('api.user.update');
});