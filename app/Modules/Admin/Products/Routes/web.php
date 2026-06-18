<?php

Route::group(['prefix' => 'products', 'middleware' => []], function () {
    Route::get('/', 'ProductsController@index')->name('products.index');
    Route::get('/create', 'ProductsController@create')->name('products.create');
    Route::post('/', 'ProductsController@store')->name('products.store');
    Route::get('/{product}', 'ProductsController@show')->name('products.read');
    Route::get('/edit/{product}', 'ProductsController@edit')->name('products.edit');
    Route::put('/{product}', 'ProductsController@update')->name('products.update');
    Route::delete('/{product}', 'ProductsController@destroy')->name('products.delete');
});