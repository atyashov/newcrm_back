<?php

Route::group(['prefix' => 'dashboard', 'middleware' => []], function () {
    Route::get('/', 'DashboardController@index')->name('dashboard.index');
});
