<?php

Route::group(['prefix' => 'outlays', 'middleware' => []], function () {
    Route::post('/', 'Api\OutlaysController@index')->name('api.outlays.index');
    Route::post('/store', 'Api\OutlaysController@store')->name('api.outlays.store');
    Route::get('/receipts', 'Api\OutlaysController@receipts')->name('api.outlays.receipts');
    Route::get('/documents/period-pdf', 'Api\OutlaysController@periodPdf')->middleware('throttle:10,1')->name('api.outlays.documents.period');
    Route::get('/{outlay}/info', 'Api\OutlaysController@info')->name('api.outlays.info');
    Route::get('/{outlay}/audit', 'Api\OutlaysController@audit')->name('api.outlays.audit');
    Route::get('/{outlay}/documents', 'Api\OutlaysController@documents')->name('api.outlays.documents.index');
    Route::get('/{outlay}/documents/{type}', 'Api\OutlaysController@document')
        ->whereIn('type', array_keys(\App\Modules\Admin\Outlays\Services\OutlayDocumentService::TYPES))
        ->middleware('throttle:20,1')->name('api.outlays.documents.download');
    Route::post('/{outlay}/public-link/rotate', 'Api\OutlaysController@rotatePublicLink')->name('api.outlays.public-link.rotate');
    Route::patch('/{outlay}/status', 'Api\OutlaysController@status')->name('api.outlays.status');
    Route::patch('/{outlay}/assignments', 'Api\OutlaysController@assignments')->name('api.outlays.assignments');
    Route::patch('/{outlay}/items/{item}/stages', 'Api\OutlaysController@stages')->whereNumber('item')->name('api.outlays.items.stages');
    Route::patch('/{outlay}/items/{item}/comments', 'Api\OutlaysController@comments')->whereNumber('item')->name('api.outlays.items.comments');
    Route::patch('/{outlay}/work-notes', 'Api\OutlaysController@workNotes')->name('api.outlays.work-notes');
    Route::get('/{outlay}', 'Api\OutlaysController@show')->name('api.outlays.read');
    Route::put('/{outlay}', 'Api\OutlaysController@update')->name('api.outlays.update');
    Route::delete('/{outlay}', 'Api\OutlaysController@destroy')->name('api.outlays.delete');
});
