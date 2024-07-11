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

Route::superGroup('admin' ,function () {
    Route::get('product-comment', 'ProductCommentController@index')->hasPermission('read_productComment');
    Route::get('product-comment/{id}', 'ProductCommentController@show')->hasPermission('read_productComment');
    Route::post('product-comment', 'ProductCommentController@assignStatus')->hasPermission('write_productComment');
    Route::post('product-comment-answer', 'ProductCommentController@answer')->hasPermission('write_productComment');
    Route::delete('product-comment/{id}', 'ProductCommentController@destroy')->hasPermission('delete_productComment');
});

Route::superGroup('customer' ,function () {
    Route::apiResource('product-comment', 'ProductCommentController');
});

Route::superGroup('front' ,function () {
    Route::get('product-comment/{product_id}', 'ProductCommentController@show');
}, []);
