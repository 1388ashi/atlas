<?php

use Illuminate\Http\Request;


Route::superGroup('admin', function () {
    Route::permissionResource('size_chart'  , 'SizeChartController');
    Route::permissionResource('size_chart_types'  , 'SizeChartTypeController',
        ['permission_name' => 'size_chart']);

});
