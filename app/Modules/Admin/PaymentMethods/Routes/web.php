<?php

Route::group(['prefix' => 'payment-methods', 'middleware' => []], function () {
    Route::get('/', 'PaymentMethodsController@index')->name('payment-methods.index');
    Route::get('/create', 'PaymentMethodsController@create')->name('payment-methods.create');
    Route::post('/', 'PaymentMethodsController@store')->name('payment-methods.store');
    Route::get('/{paymentMethod}', 'PaymentMethodsController@show')->name('payment-methods.read');
    Route::get('/edit/{paymentMethod}', 'PaymentMethodsController@edit')->name('payment-methods.edit');
    Route::put('/{paymentMethod}', 'PaymentMethodsController@update')->name('payment-methods.update');
    Route::delete('/{paymentMethod}', 'PaymentMethodsController@destroy')->name('payment-methods.delete');
});