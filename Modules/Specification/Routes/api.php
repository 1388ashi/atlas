<?php

Route::superGroup('admin', function () {
    \Illuminate\Support\Facades\Route::post('specifications/sort', 'SpecificationController@sort')
        ->name('specifications.sort');
    Route::permissionResource('specifications', 'SpecificationController');
    Route::permissionResource('specification-values', 'SpecificationValueController', ['only' =>['update', 'destroy']]);
});
