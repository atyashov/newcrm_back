<?php

Route::group(['prefix' => 'expenses', 'middleware' => []], function () {
    Route::get('/', 'Api\ExpensesController@index')->name('api.expenses.index');
    Route::post('/', 'Api\ExpensesController@store')->name('api.expenses.store');
    Route::get('/{expense}', 'Api\ExpensesController@show')->name('api.expenses.read');
    Route::put('/{expense}', 'Api\ExpensesController@update')->name('api.expenses.update');
    Route::delete('/{expense}', 'Api\ExpensesController@destroy')->name('api.expenses.delete');
});