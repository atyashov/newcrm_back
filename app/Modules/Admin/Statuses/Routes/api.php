<?php

Route::group(['prefix' => 'statuses', 'middleware' => []], function () {
    Route::get('/', 'Api\StatusesController@index')->name('api.statuses.index');
    Route::post('/with-outlays', 'Api\StatusesController@withOutlays')->name('api.statuses.with-outlays');
    Route::post('/', 'Api\StatusesController@store')->name('api.statuses.store');
    Route::get('/{status}', 'Api\StatusesController@show')->name('api.statuses.read');
    Route::put('/{status}', 'Api\StatusesController@update')->name('api.statuses.update');
    Route::delete('/{status}', 'Api\StatusesController@destroy')->name('api.statuses.delete');
});
