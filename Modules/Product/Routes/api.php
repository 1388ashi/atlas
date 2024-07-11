<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Front\ProductController;
use Modules\Product\Http\Controllers\Front\RecommendationController;
use Modules\Product\Http\Controllers\Customer\ProductController as CustomerProductController;

require base_path('vendor/shetabit/shopit/src/Modules/Product/Routes/api.php');

//    Route::get('products/search', [AllProductController::class, 'search'])->name('products.search');
//    Route::get('products', [AllProductController::class, 'index'])->name('products.index');
//    Route::get('products/compare', [CompareController::class, 'index'])->name('product.compare');
//    Route::get('products/compare/search', [CompareController::class, 'search'])->name('product.compare.search');
Route::get('front/products_light/{product}', [ProductController::class, 'show_light'])->name('product.show');

Route::superGroup("customer" ,function (){
    Route::post('products/{variety}/listen', 'ListenChargeController@store')->name('products.listen');
    Route::delete('products/{variety}/unlisten', 'ListenChargeController@destroy')->name('products.unlisten');
    Route::get('favorites', [CustomerProductController::class, 'indexFavorites'])->name('favorites.indexFavorites');
    Route::post('products/{product}/favorite', [CustomerProductController::class, 'addToFavorites'])->name('product.addToFavorites');
    Route::delete('products/{product}/favorite', [CustomerProductController::class, 'deleteFromFavorites'])->name('product.deleteFromFavorites');
});


