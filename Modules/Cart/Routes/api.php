<?php

use Illuminate\Http\Request;
use Modules\Cart\Http\Controllers\Admin\CartController;
use Modules\Cart\Http\Controllers\Customer\CartController as CustomerCartController;
use Modules\Cart\Http\Controllers\All\CartController as AllCartController;

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
Route::superGroup('admin' ,  function() {
    Route::get('cart/' , [CartController::class , 'index'])->name('cart.index');
    Route::post('cart/add/{variety}' , [CartController::class , 'add'])->name('cart.add');
    Route::delete('cart/{cart}' , [CartController::class , 'remove'])->name('cart.remove');
    Route::put('cart/{cart}' , [CartController::class , 'update'])->name('cart.update');
});

Route::superGroup('customer' ,  function() {
    Route::get('cart/' , [CustomerCartController::class , 'index'])->name('cart.index');
    Route::post('cart/add/{variety}' , [CustomerCartController::class , 'add'])->name('cart.add');
    Route::delete('cart/{cart}' , [CustomerCartController::class , 'remove'])->name('cart.remove');
    Route::put('cart/{cart}' , [CustomerCartController::class , 'update'])->name('cart.update');
});

# Route For All

Route::superGroup('all' ,  function() {
    Route::get('cart/' , [AllCartController::class , 'index'])->name('cart.index');
    Route::get('cart/get' , [AllCartController::class , 'getCarts'])->name('cart.get');
},[]);

Route::superGroup('front',function () {
    Route::get('/get-cartRequest', [\Modules\Cart\Http\Controllers\Front\CartController::class, 'getCartFromRequest']);
}, []);
