<?php

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

Route::get('front/home' , 'HomeController@index')->name('front.home.index');
Route::get('front/home_light' , 'HomeController@index_light')->name('front.home.index2');

Route::get('/front/gateways',[\Modules\Invoice\Http\Controllers\All\GatewayController::class,'index'])->name('gateways');
Route::get('front/suggestions' , 'HomeController@getSuggestions')->name('front.suggestions');
Route::get('front/most-discounts' , 'HomeController@getMostDiscounts')->name('front.most-discounts');
Route::get('front/most-sales' , 'HomeController@getMostSales')->name('front.most-sales');
Route::get('front/new-products' , 'HomeController@getNewProducts')->name('front.new-products');
Route::get('front/sliders' , 'HomeController@getSliders')->name('front.sliders');
Route::get('front/advertise' , 'HomeController@getAdvertise')->name('front.advertise');
Route::get('front/special-category' , 'HomeController@getSpecialCategory')->name('front.special-category');
Route::get('front/discount-product' , 'HomeController@getDiscountProduct')->name('front.discount-product');
Route::get('front/vip-unpublished-products' , 'HomeController@getVipUnpublishedProducts')->name('front.vip-unpublished-products');

Route::get('front/new-products-light' , 'HomeController@getNewProductsLight')->name('front.new-products2');
