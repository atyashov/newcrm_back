<?php

Route::group(['prefix' => 'client-types', 'middleware' => []], function () {
    Route::get('/', 'Api\ClientTypesController@index')->name('api.client-types.index');
    Route::post('/', 'Api\ClientTypesController@store')->name('api.client-types.store');
    Route::get('/{clientType}', 'Api\ClientTypesController@show')->name('api.client-types.read');
    Route::put('/{clientType}', 'Api\ClientTypesController@update')->name('api.client-types.update');
    Route::delete('/{clientType}', 'Api\ClientTypesController@destroy')->name('api.client-types.delete');
});