<?php
Route::group(['prefix' => 'file', 'middleware' => []], function () {
    Route::get('/', 'Api\FileController@index')->name('api.file.index');
    Route::post('/', 'Api\FileController@upload')->name('api.file.upload');
});
