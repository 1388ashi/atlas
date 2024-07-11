<?php

Route::superGroup('admin', function () {
    Route::post('contacts/{contact}/read', 'ContactController@read')->hasPermission('contact_modify');
    Route::permissionResource('contacts', 'ContactController', ['only' => ['index', 'show', 'destroy']]);
});

Route::superGroup('all', function () {
    Route::resource('contacts', 'ContactController')->only(['store']);
    Route::get('contacts/create', 'ContactController@create');
}, []);

