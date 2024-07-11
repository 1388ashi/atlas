<?php

use Illuminate\Http\Request;
use Modules\CRM\Http\Controllers\CustomerDataController;

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

//Route::middleware('auth:api')->get('/crm', function (Request $request) {
//    return $request->user();
//});

Route::superGroup('admin' ,function () {
    Route::group(['prefix' => 'crm'], function () {
        Route::get('customer-data', [CustomerDataController::class, 'showData']); // نمایش اطلاعات کامل مشتری
    });
});
