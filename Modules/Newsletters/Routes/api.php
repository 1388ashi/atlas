<?php

use Modules\Newsletters\Entities\UsersNewsletters;
use Modules\Newsletters\Http\Controllers\Admin\NewslettersController as AdminNewslettersController;
use Modules\Newsletters\Http\Controllers\Admin\UsersNewslettersController;
use Modules\Newsletters\Http\Controllers\Front\NewslettersController as FrontNewslettersController;

Route::superGroup('admin' ,function () {

    Route::get('newsletters/users', [UsersNewslettersController::class, 'index'])
        ->hasPermission('read_newsletters');

    Route::delete('newsletters/users/{users_newsletters}', [UsersNewslettersController::class, 'destroy'])
        ->hasPermission('delete_newsletters');

    Route::get('newsletters', [AdminNewslettersController::class, 'index'])
        ->hasPermission('read_newsletters');

    Route::get('newsletters/{newsletters}', [AdminNewslettersController::class, 'show'])
        ->hasPermission('read_newsletters');

    Route::post('newsletters', [AdminNewslettersController::class, 'send'])
        ->hasPermission('write_newsletters');

    Route::delete('newsletters/{newsletters}', [AdminNewslettersController::class, 'destroy'])
        ->hasPermission('delete_newsletters');
});

Route::superGroup('front', function(){
    Route::post('newsletters', [FrontNewslettersController::class, 'store']);
},[]);
