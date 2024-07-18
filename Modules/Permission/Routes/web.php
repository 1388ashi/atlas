<?php
use Illuminate\Support\Facades\Route;
use Modules\Permission\Http\Controllers\Admin\RoleController;

Route::webSuperGroup('admin', function () {
    //auth
    Route::get('/roles', [RoleController::class,'webIndex'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class,'webCreate'])->name('roles.create');
    Route::post('/roles', [RoleController::class,'webStore'])->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class,'webEdit'])->name('roles.edit');
    Route::patch('/roles/{role}', [RoleController::class,'webUpdate'])->name('roles.update');
    Route::delete('/roles/delete/{role}', [RoleController::class,'webDestroy'])->name('roles.destroy');
});
