<?php

use Modules\Flash\Http\Controllers\Front\FlashController;

Route::superGroup('admin', function () {
    Route::permissionResource('flashes', 'FlashController');
});

Route::prefix('/front')->namespace('Front')->as('front.')->group(function () {
    Route::get('/flashes', [FlashController::class, 'index'])->name('flashes');
    Route::get('/flashes/{flash}', [FlashController::class, 'show'])->name('flashes.show');
});
