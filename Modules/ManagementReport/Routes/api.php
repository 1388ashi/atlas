<?php

use Illuminate\Http\Request;
use Modules\ManagementReport\Http\Controllers\BuyersReportController;
use Modules\ManagementReport\Http\Controllers\CategorizedReportController;
use Modules\ManagementReport\Http\Controllers\CustomerRegistrationReportController;
use Modules\ManagementReport\Http\Controllers\HighestCustomerOrderAmountReportController;
use Modules\ManagementReport\Http\Controllers\HighestCustomerOrderCountReportController;
use Modules\ManagementReport\Http\Controllers\HighestOrderPriceReportController;
use Modules\ManagementReport\Http\Controllers\MostSaleReportController;
use Modules\ManagementReport\Http\Controllers\MostViewReportController;
use Modules\ManagementReport\Http\Controllers\NonBuyersReportController;
use Modules\ManagementReport\Http\Controllers\SellBenefitReportController;
use Modules\ManagementReport\Http\Controllers\SellThresholdReportController;
use Modules\ManagementReport\Http\Controllers\WalletChargeReportController;
use Modules\ManagementReport\Http\Controllers\WebsiteViewReportController;

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

//Route::middleware('auth:api')->get('/managementreport', function (Request $request) {
//    return $request->user();
//});

Route::superGroup('admin' ,function () {
    Route::group(['prefix' => 'management-report'] , function (){
        Route::get('most-sale',[MostSaleReportController::class,'makeReport']); // پرفروش ترین محصول
        Route::get('most-view',[MostViewReportController::class,'makeReport']); // پربازدیدترین محصول
        Route::get('customer-registration',[CustomerRegistrationReportController::class,'makeReport']); // ثبت نام منجر به خرید
        Route::get('website-view',[WebsiteViewReportController::class,'makeReport']); // بازدید وب سایت
        Route::get('sell-benefit',[SellBenefitReportController::class,'makeReport']); // فروش و سود
        Route::get('categorized-report',[CategorizedReportController::class,'makeReport']); // گزارش تفکیکی
        Route::get('buyers',[BuyersReportController::class,'makeReport']); // خرید کرده ها
        Route::get('non-buyers',[NonBuyersReportController::class,'makeReport']); // خرید نکرده ها
        Route::get('wallet-charges',[WalletChargeReportController::class,'makeReport']); // شارژ کیف پول
        Route::get('sell-threshold',[SellThresholdReportController::class,'makeReport']); // آستانه فروش
        Route::get('highest-order-price',[HighestOrderPriceReportController::class,'makeReport']); // بیشترین قیمت سفارشات
        Route::get('highest-customer-order-amount',[HighestCustomerOrderAmountReportController::class,'makeReport']); // بیشترین مبلغ و تعداد خرید کل مشتریان
    });
});
