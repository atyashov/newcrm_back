<?php

Route::get('outlays/{uuid}', 'Api\OutlaysController@show')->whereUuid('uuid')
    ->middleware('throttle:60,1')->name('api.pub.outlays.show');
