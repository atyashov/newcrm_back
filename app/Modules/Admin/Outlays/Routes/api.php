<?php

Route::group(['prefix' => 'outlays', 'middleware' => []], function () {
    Route::post('/', 'Api\OutlaysController@index')->name('api.outlays.index');
    Route::post('/store', 'Api\OutlaysController@store')->name('api.outlays.store');
//    Route::get('/{outlay}', 'Api\OutlaysController@show')->name('api.outlays.read');
//    Route::put('/{outlay}', 'Api\OutlaysController@update')->name('api.outlays.update');
//    Route::delete('/{outlay}', 'Api\OutlaysController@destroy')->name('api.outlays.delete');
});
