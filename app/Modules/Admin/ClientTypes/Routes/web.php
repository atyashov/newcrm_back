<?php

Route::group(['prefix' => 'client-types', 'middleware' => []], function () {
    Route::get('/', 'ClientTypesController@index')->name('client-types.index');
    Route::get('/create', 'ClientTypesController@create')->name('client-types.create');
    Route::post('/', 'ClientTypesController@store')->name('client-types.store');
    Route::get('/{clientType}', 'ClientTypesController@show')->name('client-types.read');
    Route::get('/edit/{clientType}', 'ClientTypesController@edit')->name('client-types.edit');
    Route::put('/{clientType}', 'ClientTypesController@update')->name('client-types.update');
    Route::delete('/{clientType}', 'ClientTypesController@destroy')->name('client-types.delete');
});