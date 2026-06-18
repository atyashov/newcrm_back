<?php

Route::group(['prefix' => 'subdivisions', 'middleware' => []], function () {
    Route::get('/', 'SubdivisionsController@index')->name('subdivisions.index');
    Route::get('/create', 'SubdivisionsController@create')->name('subdivisions.create');
    Route::post('/', 'SubdivisionsController@store')->name('subdivisions.store');
    Route::get('/{subdivision}', 'SubdivisionsController@show')->name('subdivisions.read');
    Route::get('/edit/{subdivision}', 'SubdivisionsController@edit')->name('subdivisions.edit');
    Route::put('/{subdivision}', 'SubdivisionsController@update')->name('subdivisions.update');
    Route::delete('/{subdivision}', 'SubdivisionsController@destroy')->name('subdivisions.delete');
});