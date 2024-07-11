<?php


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

use Illuminate\Support\Facades\Route;

Route::superGroup('admin', function () {
    Route::get('menu_items/groups', 'MenuItemController@groups');
    Route::get('menu_items/create', 'MenuItemController@create');
    Route::post('menu_items/sort', 'MenuItemController@sort');
    Route::get('menu_items/{group}', 'MenuItemController@index');
    Route::apiResource('menu_items', 'MenuItemController')->except('index');
});

Route::superGroup('all', function () {
    Route::apiResource('menu_items', 'MenuItemController')->only('index', 'show');
    Route::get('menus/{groupTitle}', 'MenuItemController@menus');
}, []);
