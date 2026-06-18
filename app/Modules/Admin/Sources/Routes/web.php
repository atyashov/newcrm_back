<?php

Route::group(['prefix' => 'sources', 'middleware' => []], function () {
    Route::get('/', 'SourcesController@index')->name('sources.index');
    Route::get('/create', 'SourcesController@create')->name('sources.create');
    Route::post('/', 'SourcesController@store')->name('sources.store');
    Route::get('/{source}', 'SourcesController@show')->name('sources.read');
    Route::get('/edit/{source}', 'SourcesController@edit')->name('sources.edit');
    Route::put('/{source}', 'SourcesController@update')->name('sources.update');
    Route::delete('/{source}', 'SourcesController@destroy')->name('sources.delete');
});