<?php
use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Admin\AuthController;


Route::get('/', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/' , [AuthController::class, 'loginWeb'])->name('login');
Route::webSuperGroup('admin', function () {
    //auth
    Route::post('/webLogout', [AuthController::class, 'webLogout'])->name('webLogout');
});

//Customer routes
// Route::superGroup('customer', function () {
//     //register/login
//     Route::post('/register-login' , [CustomerAuthController::class, 'registerLogin'])->name('registerLogin');
//     Route::post('/register' , [CustomerAuthController::class, 'register'])->name('register');
//     Route::post('/login' , [CustomerAuthController::class, 'login'])->name('login');
//     Route::post('/send/token' , [CustomerAuthController::class, 'sendToken'])->name('sendToken');
//     Route::post('/verify' , [CustomerAuthController::class, 'verify'])->name('verify');
//     //reset password
//     Route::put('/password/reset' , [CustomerAuthController::class, 'resetPassword'])
//         ->name('password.reset');
// }, ['throttle:sms','block_ip_range']);

// Route::superGroup('customer', function () {
//     Route::post('/device-token' , [CustomerAuthController::class, 'setDeviceToken'])->name('deviceToken');
//     Route::post('/logout' , [CustomerAuthController::class, 'logout'])->name('logout');
// });
