<?php

Route::group(['prefix' => 'menus', 'middleware' => []], function () {
    Route::get('/', 'Api\MenuController@index')->name('api.menus.index');
    Route::get('/manage', 'Api\MenuController@manage')->name('api.menus.manage');
    Route::patch('/{menu}', 'Api\MenuController@update')->name('api.menus.update');
});
