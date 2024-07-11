<?php

use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\Admin\StoreTransactionController;
use Modules\Store\Http\Controllers\Admin\StoreController as AdminStoreController;
Route::superGroup('admin', function() {
    Route::name('store_transactions')
        ->get('store_transactions' , 'StoreTransactionController@index')
    ->hasPermission('read_store');
    Route::get('store/store-wealth-report',[AdminStoreController::class,'storeWealthReport']);

    Route::prefix('store_transactions/statuses')->group(function(){
        Route::get('/pending_list',[StoreTransactionController::class,'pending_list']);
        Route::post('/mark-as-done',[StoreTransactionController::class,'markAsDoneBatch']);
        Route::put('/{transaction}/mark-as-done',[StoreTransactionController::class,'markAsDone']);

    });

    Route::permissionResource('stores' , 'StoreController', ['only' => ['index', 'show', 'store']]);
});
