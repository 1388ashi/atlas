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

Route::superGroup('admin', function () {
    Route::get('sliders/groups', 'SliderController@groups')->hasPermission('read_slider');
    Route::get('sliders/groups/{group}', 'SliderController@index')->hasPermission('read_slider');
    Route::post('sliders/sort', 'SliderController@sort')->hasPermission('write_slider');
    Route::permissionResource('sliders', 'SliderController');
});

Route::superGroup('all', function () {
    Route::get('sliders', 'SliderController@index');
}, []);
