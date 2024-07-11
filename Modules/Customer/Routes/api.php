<?php

require base_path('vendor/shetabit/shopit/src/Modules/Customer/Routes/api.php');

Route::superGroup('front',function () {
    Route::get('/get-user', [\Modules\Customer\Http\Controllers\Front\CustomerController::class, 'getUser']);
}, []);
