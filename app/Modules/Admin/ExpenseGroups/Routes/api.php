<?php

Route::group(['prefix' => 'expense-groups', 'middleware' => []], function () {
    Route::get('/', 'Api\ExpenseGroupsController@index')->name('api.expense-groups.index');
    Route::post('/', 'Api\ExpenseGroupsController@store')->name('api.expense-groups.store');
    Route::get('/{expenseGroup}', 'Api\ExpenseGroupsController@show')->name('api.expense-groups.read');
    Route::put('/{expenseGroup}', 'Api\ExpenseGroupsController@update')->name('api.expense-groups.update');
    Route::delete('/{expenseGroup}', 'Api\ExpenseGroupsController@destroy')->name('api.expense-groups.delete');
});