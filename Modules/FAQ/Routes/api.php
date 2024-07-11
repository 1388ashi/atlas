<?php

Route::superGroup('admin',function () {
    Route::post('f_a_qs/sort', 'FAQController@sort')->hasPermission('modify_faq');
    Route::permissionResource('f_a_qs', 'FAQController');
});

Route::superGroup('front',function () {
    Route::apiResource('f_a_qs', 'FAQController')->only('index', 'show');
}, []);
