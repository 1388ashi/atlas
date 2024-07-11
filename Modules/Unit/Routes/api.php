<?php


Route::superGroup('admin', function () {
    Route::permissionResource('units','UnitController');
});
