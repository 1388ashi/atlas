<?php

Route::superGroup('admin', function () {
    Route::permissionResource('colors','ColorController');
});

Route::superGroup('front',function () {
    Route::get('/get-colors', [\Modules\Color\Http\Controllers\Front\ColorController::class, 'getColors']);
}, []);
