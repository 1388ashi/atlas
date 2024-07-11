<?php

use Illuminate\Http\Request;
use Modules\Permission\Http\Controllers\Admin\RoleController;

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

Route::superGroup('admin' ,function () {
    Route::apiResource('permissions', 'PermissionController')->only('index');
    Route::permissionResource('roles', 'RoleController');
});
