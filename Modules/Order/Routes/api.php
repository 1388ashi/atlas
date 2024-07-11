<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\Admin\OrderController;
Route::superGroup('admin', function () {
    Route::prefix('order_items/statuses')->group(function(){
        Route::get('/pending_list',[OrderController::class,'pending_list']);
        Route::post('/mark-as-done',[OrderController::class,'markAsDone']);
        // Route::put('/{orderItem}/mark-as-done',[OrderController::class,'markAsDone']);
    });
});

require base_path('vendor/shetabit/shopit/src/Modules/Order/Routes/api.php');
