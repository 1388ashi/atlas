<?php

use Illuminate\Http\Request;
use Modules\Coupon\Http\Controllers\Customer\CouponController as CustomerCouponController;


Route::superGroup('admin' ,function () {
    Route::permissionResource('coupon', CouponController::class);
});

Route::superGroup('customer' ,function () {
    Route::post('/coupon/verify', [CustomerCouponController::class, 'verify'])->name('coupon_verify');
});
