<?php

Route::superGroup('admin', function (){
    Route::get('instagram', 'InstagramController@index')->hasPermission('read_instagram');
    Route::put('instagram', 'InstagramController@update')->hasPermission('modify_instagram');
});
