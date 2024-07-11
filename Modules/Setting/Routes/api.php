<?php

require base_path('vendor/shetabit/shopit/src/Modules/Setting/Routes/api.php');

Route::superGroup('front',function () {
    Route::get('/get-settings', [\Modules\Setting\Http\Controllers\Front\SettingController::class, 'getSetting']);
}, []);
