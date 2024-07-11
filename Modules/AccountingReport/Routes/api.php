<?php

use Illuminate\Http\Request;
use Modules\AccountingReport\Http\Controllers\GatewayReportController;
use Modules\AccountingReport\Http\Controllers\OrderSellReportController;
use Modules\AccountingReport\Http\Controllers\ProductSellReportController;
use Modules\AccountingReport\Http\Controllers\WalletTransactionReportController;

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

//Route::middleware('auth:api')->get('/accountingreport', function (Request $request) {
//    return $request->user();
//});

//Route::superGroup('admin' ,function () {
//    Route::get('accounting_report/product_sell',[ProductSellReportController::class,'makeReport']);
//}, ['auth:admin-api', 'permission:report,admin-api']);

Route::superGroup('admin' ,function () {
    Route::group(['prefix' => 'accounting-report'] , function (){
        Route::get('product-sell',[ProductSellReportController::class,'makeReport']);
        Route::get('order-sell',[OrderSellReportController::class,'makeReport']);
        Route::get('wallet-transaction',[WalletTransactionReportController::class,'makeReport']);
        Route::get('gateway',[GatewayReportController::class,'makeReport']);
    });
});

//Route::get('product-sell',[ProductSellReportController::class,'makeReport']);
