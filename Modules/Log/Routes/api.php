<?php


Route::superGroup('admin' ,function () {
    Route::get('logs/models', 'LogController@logModelsList')
        ->hasPermission('read_log');
    Route::permissionResource('logs', 'LogController');
});
