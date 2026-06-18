<?php

Route::group(['prefix' => 'subdivisions', 'middleware' => []], function () {
    Route::get('/', 'Api\SubdivisionsController@index')->name('api.subdivisions.index');
    Route::post('/', 'Api\SubdivisionsController@store')->name('api.subdivisions.store');
    Route::get('/{subdivision}', 'Api\SubdivisionsController@show')->name('api.subdivisions.read');
    Route::put('/{subdivision}', 'Api\SubdivisionsController@update')->name('api.subdivisions.update');
    Route::delete('/{subdivision}', 'Api\SubdivisionsController@destroy')->name('api.subdivisions.delete');
});