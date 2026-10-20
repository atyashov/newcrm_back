<?php

Route::group(['prefix' => 'borrowings', 'middleware' => []], function () {
    Route::get('/', 'Api\BorrowingsController@index')->name('api.borrowings.index');
    Route::post('/', 'Api\BorrowingsController@store')->name('api.borrowings.store');
    Route::get('/{borrowing}', 'Api\BorrowingsController@show')->name('api.borrowings.read');
    Route::put('/{borrowing}', 'Api\BorrowingsController@update')->name('api.borrowings.update');
    Route::delete('/{borrowing}', 'Api\BorrowingsController@destroy')->name('api.borrowings.delete');
});