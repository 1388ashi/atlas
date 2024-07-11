<?php

use Modules\Report\Http\Controllers\Admin\ReportController;

require base_path('vendor/shetabit/shopit/src/Modules/Report/Routes/api.php');

Route::superGroup('admin' ,function () {
    Route::get('reports/customers/incomes-detail', 'ReportController@customersIncomesDetail')->name('reports.customersIncomesDetail');

    Route::get('reports/report_discount/{start_date}/{end_date}',[ReportController::class,'reportDiscountBetweenDates']);

    Route::get('reports/products-balance',[ReportController::class,'productsBalance']);

    Route::get('reports/sell-types',[ReportController::class,'sellTypes']);

    Route::get('reports/walletsExcel', [ReportController::class,'walletsExcel'])->name('reports.walletsExcel');

    Route::get('reports/customersExcel', [ReportController::class,'customersExcel'])->name('reports.customersExcel');

}, ['auth:admin-api', 'permission:report,admin-api']);

Route::get('report_varieties',[ReportController::class,'varietiesReport']);
Route::get('report_products',[ReportController::class,'productsReport']);

Route::get('report_discount/{start_date}/{end_date}',[ReportController::class,'reportDiscountBetweenDatesHtml']);



Route::get('report_customer',[ReportController::class,'publicReportCustomer']);
Route::get('report_variety',[ReportController::class,'publicReportVariety']);
Route::get('report_full',[ReportController::class,'publicReportFull']);
