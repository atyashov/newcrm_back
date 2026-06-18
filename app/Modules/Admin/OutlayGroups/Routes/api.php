<?php

Route::group(['prefix' => 'outlay-groups', 'middleware' => []], function () {
    Route::get('/', 'Api\OutlayGroupsController@index')->name('api.outlay-groups.index');
    Route::post('/', 'Api\OutlayGroupsController@store')->name('api.outlay-groups.store');
    Route::get('/{outlayGroup}', 'Api\OutlayGroupsController@show')->name('api.outlay-groups.read');
    Route::put('/{outlayGroup}', 'Api\OutlayGroupsController@update')->name('api.outlay-groups.update');
    Route::delete('/{outlayGroup}', 'Api\OutlayGroupsController@destroy')->name('api.outlay-groups.delete');
});