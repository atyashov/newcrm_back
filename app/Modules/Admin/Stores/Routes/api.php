<?php

Route::group(['prefix' => 'stores', 'middleware' => []], function () {
    Route::get('/', 'Api\StoresController@index')->middleware('can:view,App\Modules\Admin\Stores\Models\Store')->name('api.stores.index');
    Route::post('/', 'Api\StoresController@store')->middleware('can:manage,App\Modules\Admin\Stores\Models\Store')->name('api.stores.store');
    Route::get('/{store}', 'Api\StoresController@show')->middleware('can:view,store')->name('api.stores.read');
    Route::put('/{store}', 'Api\StoresController@update')->middleware('can:manage,store')->name('api.stores.update');
    Route::delete('/{store}', 'Api\StoresController@destroy')->middleware('can:manage,store')->name('api.stores.delete');
});
