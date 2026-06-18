<?php

Route::group(['prefix' => 'subjects', 'middleware' => []], function () {
    Route::get('/', 'Api\SubjectsController@index')->name('api.subjects.index');
    Route::post('/', 'Api\SubjectsController@store')->name('api.subjects.store');
    Route::get('/{subject}', 'Api\SubjectsController@show')->name('api.subjects.read');
    Route::put('/{subject}', 'Api\SubjectsController@update')->name('api.subjects.update');
    Route::delete('/{subject}', 'Api\SubjectsController@destroy')->name('api.subjects.delete');
});