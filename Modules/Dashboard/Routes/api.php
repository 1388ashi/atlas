<?php

require base_path('vendor/shetabit/shopit/src/Modules/Dashboard/Routes/api.php');

use Illuminate\Support\Facades\Route;
use \Shetabit\Shopit\Modules\Dashboard\Http\Controllers\Admin\SiteViewController as AdminSiteViewController;
use Modules\Dashboard\Http\Controllers\Admin\DashboardController as AdminDashboardController;

Route::superGroup('admin', function () {
    Route::get('dashboards/siteViews',[AdminDashboardController::class,'siteViews']);
    Route::get('dashboards/comments',[AdminDashboardController::class,'comments']);
    Route::get('dashboards/lastOrders',[AdminDashboardController::class,'lastOrders']);
    Route::get('dashboards/contacts',[AdminDashboardController::class,'contacts']);
    Route::get('dashboards/lastLogins',[AdminDashboardController::class,'lastLogins']);
    Route::get('dashboards/ordersCount',[AdminDashboardController::class,'ordersCount']);
    Route::get('dashboards/todayOrdersCount',[AdminDashboardController::class,'todayOrdersCount']);
    Route::get('dashboards/salesAmountByToday',[AdminDashboardController::class,'salesAmountByToday']);
    Route::get('dashboards/salesAmountByMonth',[AdminDashboardController::class,'salesAmountByMonth']);
    Route::get('dashboards/genderStatistics',[AdminDashboardController::class,'genderStatistics']);
    Route::get('dashboards/logs',[AdminDashboardController::class,'logs']);
    Route::get('dashboards/orderByStatus',[AdminDashboardController::class,'orderByStatus']);

    Route::apiResource('dashboard', 'DashboardController');
    Route::get('siteviews', [AdminSiteViewController::class, 'index']);
    Route::get('siteviews/{siteview}', [AdminSiteViewController::class, 'show']);
}, ['auth:admin-api', 'permission:report,admin-api']);
