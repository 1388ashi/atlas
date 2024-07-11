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

Route::superGroup('customer', function () {
    Route::post('notifications/read', 'NotificationController@read')->name('notifications.read');
    Route::get('notifications', 'NotificationController@index')->name('notifications.index');
});
Route::superGroup('admin', function () {
    Route::post('notifications/read', 'NotificationController@read')->name('notifications.read');
    Route::get('notifications', 'NotificationController@index')->name('notifications.index');
});
