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

//Route::superGroup('customer', function () {
//    Route::get('widgets', 'WidgetController@index')->name('widgets.index');
//});
Route::superGroup('admin', function () {
    Route::get('widgets', 'WidgetController@index')->name('widgets.index');
});
