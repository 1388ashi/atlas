<?php
use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Admin\ProfileController;

Route::webSuperGroup('admin', function () {
    // Route::get('admins', 'AdminController');
    Route::resource('admins', 'AdminController');

    Route::put('/password', [ProfileController::class, 'changePassword'])->name('password');
});
