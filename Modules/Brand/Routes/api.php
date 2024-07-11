<?php

Route::superGroup('admin', function(){
        Route::permissionResource('brands' , BrandController::class);
});
