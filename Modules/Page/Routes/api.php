<?php



Route::superGroup('admin', function () {
    Route::apiResource('pages', 'PageController');
});

Route::superGroup('front', function () {
    Route::apiResource('pages', 'PageController')->only('index', 'show');
}, []);
