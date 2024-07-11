<?php

use Illuminate\Support\Facades\Route;

Route::superGroup('admin', function () {
    Route::post('gift_packages/sort','GiftPackageController@sort')->hasPermission('modify_gift_package');
    Route::permissionResource('gift_packages','GiftPackageController');
});

Route::superGroup('front', function () {
    Route::get('gift_packages','GiftPackageController@index');
},[]);
