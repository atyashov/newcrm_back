<?php

Route::group(['prefix' => 'subjects', 'middleware' => []], function () {
    Route::get('/', 'SubjectsController@index')->name('subjects.index');
    Route::get('/create', 'SubjectsController@create')->name('subjects.create');
    Route::post('/', 'SubjectsController@store')->name('subjects.store');
    Route::get('/{subject}', 'SubjectsController@show')->name('subjects.read');
    Route::get('/edit/{subject}', 'SubjectsController@edit')->name('subjects.edit');
    Route::put('/{subject}', 'SubjectsController@update')->name('subjects.update');
    Route::delete('/{subject}', 'SubjectsController@destroy')->name('subjects.delete');
});