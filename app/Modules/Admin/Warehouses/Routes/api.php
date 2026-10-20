<?php

Route::group(['prefix' => 'warehouse', 'middleware' => []], function () {
    Route::get('/stocks', 'Api\WarehousesController@stocks')->name('api.warehouse.stocks');
    Route::get('/rentals', 'Api\WarehousesController@rentals')->name('api.warehouse.rentals');
    Route::get('/stocks/{stock}/movements', 'Api\WarehousesController@movements')->name('api.warehouse.movements');
    Route::post('/stocks/{stock}/adjustments', 'Api\WarehousesController@adjust')->name('api.warehouse.adjust');
    Route::patch('/rentals/{rental}/status', 'Api\WarehousesController@updateRentalStatus')->name('api.warehouse.rentals.status');
});
