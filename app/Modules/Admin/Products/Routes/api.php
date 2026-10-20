<?php

Route::group(['prefix' => 'products', 'middleware' => []], function () {
    Route::get('/', 'Api\ProductsController@index')->name('api.products.index');
    Route::post('/', 'Api\ProductsController@store')->name('api.products.store');
    Route::get('/availability', 'Api\ProductsController@availability')->name('api.products.availability');
    Route::post('/availability', 'Api\ProductsController@batchAvailability')->name('api.products.availability.batch');
    Route::get('/simple', 'Api\ProductsController@simple')
        ->middleware('users.subdivisions')
        ->name('api.products.simple');
    Route::get('/{product}', 'Api\ProductsController@show')->name('api.products.read');
    Route::put('/{product}', 'Api\ProductsController@update')->name('api.products.update');
    Route::delete('/{product}', 'Api\ProductsController@destroy')->name('api.products.delete');
});

Route::group(['prefix' => "categories"], function () {
    Route::get('/', 'Api\CategoriesController@index')->name('api.categories.index');
    Route::post('/', 'Api\CategoriesController@store')->name('api.categories.store');
    Route::get('/{category}', 'Api\CategoriesController@show')->name('api.categories.read');
    Route::put('/{category}', 'Api\CategoriesController@update')->name('api.categories.update');
    Route::delete('/{category}', 'Api\CategoriesController@destroy')->name('api.categories.delete');
});
