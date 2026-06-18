<?php

Route::group(['prefix' => 'payment-methods', 'middleware' => []], function () {
    Route::get('/', 'Api\PaymentMethodsController@index')->name('api.payment-methods.index');
    Route::post('/', 'Api\PaymentMethodsController@store')->name('api.payment-methods.store');
    Route::get('/{paymentMethod}', 'Api\PaymentMethodsController@show')->name('api.payment-methods.read');
    Route::put('/{paymentMethod}', 'Api\PaymentMethodsController@update')->name('api.payment-methods.update');
    Route::delete('/{paymentMethod}', 'Api\PaymentMethodsController@destroy')->name('api.payment-methods.delete');
});