<?php

Route::group(['prefix' => 'individuals', 'middleware' => []], function () {
    Route::get('/', 'Api\IndividualsController@index')->name('api.clients.individuals.index');
    Route::post('/', 'Api\IndividualsController@store')->name('api.clients.individuals.store');
    Route::get('/{individual}', 'Api\IndividualsController@show')->name('api.clients.individuals.read');
    Route::put('/{individual}', 'Api\IndividualsController@update')->name('api.clients.individuals.update');
    Route::delete('/{individual}', 'Api\IndividualsController@destroy')->name('api.clients.individuals.delete');
});
Route::group(['prefix' => 'legal-entities', 'middleware' => []], function () {
    Route::get('/', 'Api\LegalEntitiesController@index')->name('api.clients.legal-entities.index');
    Route::post('/', 'Api\LegalEntitiesController@store')->name('api.clients.legal-entities.store');
    Route::get('/{legal-entity}', 'Api\LegalEntitiesController@show')->name('api.clients.legal-entities.read');
    Route::put('/{legal-entity}', 'Api\LegalEntitiesController@update')->name('api.clients.legal-entities.update');
    Route::delete('/{legal-entity}', 'Api\LegalEntitiesController@destroy')->name('api.clients.legal-entities.delete');
});
