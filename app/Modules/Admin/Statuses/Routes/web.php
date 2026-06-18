<?php

Route::group(['prefix' => 'statuses', 'middleware' => []], function () {
    Route::get('/', 'StatusesController@index')->name('statuses.index');
    Route::get('/create', 'StatusesController@create')->name('statuses.create');
    Route::post('/', 'StatusesController@store')->name('statuses.store');
    Route::get('/{status}', 'StatusesController@show')->name('statuses.read');
    Route::get('/edit/{status}', 'StatusesController@edit')->name('statuses.edit');
    Route::put('/{status}', 'StatusesController@update')->name('statuses.update');
    Route::delete('/{status}', 'StatusesController@destroy')->name('statuses.delete');
});