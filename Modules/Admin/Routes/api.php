<?php

use Illuminate\Http\Request;

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
use Modules\Admin\Http\Controllers\Admin\ProfileController;

Route::superGroup('admin', function () {
    Route::permissionResource('admin', 'AdminController');
    Route::get('home', 'HomeController@index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/image', [ProfileController::class, 'uploadImage'])->name('profile.uploadImage');
    Route::put('/password', [ProfileController::class, 'changePassword'])->name('password');
});
