<?php

Route::superGroup('admin', function () {
    Route::permissionResource('attributes', 'AttributeController');
});

Route::superGroup('front',function () {
    Route::get('/get-sizeValues', [\Modules\Attribute\Http\Controllers\Front\AttributeController::class, 'getSizeValues']);
}, []);
