<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\Admin\DashboardController;


    // Route::middleware('auth')->name('admin.')->prefix('admin')->group(function () {
    // //Dashboard
    // Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    // });

    Route::webSuperGroup('admin', function () {
        Route::get('/', [DashboardController::class, 'webIndex'])->name('dashboard');

    });
