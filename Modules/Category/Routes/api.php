<?php

Route::superGroup('admin' ,function () {
    Route::post('category/sort' , 'CategoryController@sort');
    Route::permissionResource('categories', CategoryController::class);
});

Route::superGroup('front' ,function () {
    Route::apiResource('categories', CategoryController::class)->only('index', 'show');
    Route::get('/get-categories', [\Modules\Category\Http\Controllers\Front\CategoryController::class, 'getCategories']);
},[]);
