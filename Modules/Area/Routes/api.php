<?php
use Illuminate\Support\Facades\Route;

//Route::name('admin.')->namespace('Admin')->prefix('admin')->group(function(){
//    Route::apiResource('Admin' , 'AdminController');
//});
//Route::name('user.')->namespace('User')->prefix('user')->group(function(){
//    Route::name('index')->get('/', 'UserController@index');
//    Route::name('show')->get('/{id?}', 'UserController@show');
//});

Route::superGroup('admin', function () {
    Route::get('cities', 'CityController@index')->hasPermission('read_area');
    Route::get('cities/{city}', 'CityController@show')->hasPermission('read_area');
    Route::post('cities', 'CityController@store')->hasPermission('write_area');
    Route::put('cities/{city}', 'CityController@update')->hasPermission('modify_area');
    Route::delete('cities/{city}', 'CityController@destroy')->hasPermission('delete_area');

    Route::get('provinces', 'ProvinceController@index')->hasPermission('read_area');
    Route::get('provinces/{province}', 'ProvinceController@show')->hasPermission('read_area');
    Route::post('provinces', 'ProvinceController@store')->hasPermission('write_area');
    Route::put('provinces/{province}', 'ProvinceController@update')->hasPermission('modify_area');
    Route::delete('provinces/{province}', 'ProvinceController@destroy')->hasPermission('delete_area');
});


Route::name('all.area')->get('front/area', 'AreaController@index');
