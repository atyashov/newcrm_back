<?php

Route::group(['prefix' => 'cash-boxes', 'middleware' => []], function () {
    Route::get('/', 'Api\CashBoxesController@index')->name('api.cash-boxes.index');
    Route::post('/', 'Api\CashBoxesController@store')->name('api.cash-boxes.store');
    Route::get('/summary', 'Api\CashBoxesController@summary')->name('api.cash-boxes.summary');
    Route::get('/operations', 'Api\CashBoxesController@operations')->name('api.cash-boxes.operations');
    Route::get('/{cashBox}/transactions', 'Api\CashBoxesController@transactions')->name('api.cash-boxes.transactions');
    Route::get('/{cashBox}', 'Api\CashBoxesController@show')->name('api.cash-boxes.read');
    Route::put('/{cashBox}', 'Api\CashBoxesController@update')->name('api.cash-boxes.update');
    Route::delete('/{cashBox}', 'Api\CashBoxesController@destroy')->name('api.cash-boxes.delete');
});
