<?php

use Modules\Blog\Http\Controllers\Front\PostCategoryController;
use Modules\Blog\Http\Controllers\Front\PostController;

Route::superGroup('admin', function () {
    Route::permissionResource('post-categories','PostCategoryController');
    Route::permissionResource('posts','PostController');
});

Route::prefix('/front')->namespace('Front')->as('front.')->group(function () {
    Route::get('/post-categories', [PostCategoryController::class, 'index'])->name('postCategories');
    Route::get('/posts', [PostController::class, 'index'])->name('blog.posts');
    Route::get('/posts/category/{category_id}', [PostController::class, 'byCategory'])->name('category.posts');
    Route::get('/posts/{id}', [PostController::class, 'show'])->name('posts.show');
});
