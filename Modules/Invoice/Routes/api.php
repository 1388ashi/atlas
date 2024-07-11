<?php

use Illuminate\Http\Request;
use Modules\Invoice\Http\Controllers\All\PaymentController;
use Modules\Invoice\Http\Controllers\All\VirtualGatewayController;

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

\Illuminate\Support\Facades\Route::name('payment.verify')
    ->any('payment/{gateway}/verify', [PaymentController::class, 'verify']);
\Illuminate\Support\Facades\Route::name('virtual-gateway')
    ->get('virtual-gateway/{virtual_gateway}', [VirtualGatewayController::class, 'pay']);
