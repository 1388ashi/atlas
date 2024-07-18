<?php
use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\Admin\AdminController;
use Modules\Admin\Http\Controllers\Admin\ProfileController;

Route::webSuperGroup('admin', function () {
    // Route::get('admins', 'AdminController');
    Route::get('/admins', [AdminController::class,'webIndex'])->name('admins.index');
    Route::get('/admins/create', [AdminController::class,'webCreate'])->name('admins.create');
    Route::post('/admins', [AdminController::class,'webStore'])->name('admins.store');
    Route::get('/admins/{admin}/edit', [AdminController::class,'webEdit'])->name('admins.edit');
    Route::patch('/admins/{admin}', [AdminController::class,'webUpdate'])->name('admins.update');
    Route::delete('/admins/delete/{role}', [AdminController::class,'webDestroy'])->name('admins.destroy');

    Route::put('/password', [ProfileController::class, 'changePassword'])->name('password');
});
