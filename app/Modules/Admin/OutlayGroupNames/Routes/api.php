<?php

Route::group(['prefix' => 'outlay-group-names', 'middleware' => []], function () {
    Route::get('/', 'Api\OutlayGroupNamesController@index')->name('api.outlay-group-names.index');
    Route::post('/', 'Api\OutlayGroupNamesController@store')->name('api.outlay-group-names.store');
    Route::get('/{outlayGroupName}', 'Api\OutlayGroupNamesController@show')->name('api.outlay-group-names.read');
    Route::put('/{outlayGroupName}', 'Api\OutlayGroupNamesController@update')->name('api.outlay-group-names.update');
    Route::delete('/{outlayGroupName}', 'Api\OutlayGroupNamesController@destroy')->name('api.outlay-group-names.delete');
});