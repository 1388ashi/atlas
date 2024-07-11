<?php



Route::superGroup('admin', function () {
    Route::apiResource('advertise', 'AdvertiseController');
    Route::apiResource('positions', 'PositionAdvertiseController');
    Route::post('positions/{position}/update_possibility', 'AdvertiseController@updatePossibility')
        ->name('advertisements.update_possibility');
});

Route::superGroup('all' , function (){
        Route::apiResource('advertise', 'AdvertiseController')->only('index');
}, []);
