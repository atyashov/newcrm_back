<?php

Route::group(['prefix' => 'users', 'middleware' => []], function () {
    Route::get('/', 'Api\UserController@index')->name('api.users.index');
    Route::get('/form', 'Api\UserController@usersForm')->name('api.users.form');
    Route::post('/', 'Api\UserController@store')->middleware('can:create,App\Modules\Admin\User\Models\User')->name('api.users.store');
    Route::get('/{user}', 'Api\UserController@show')->name('api.users.read');
    Route::put('/{user}', 'Api\UserController@update')->middleware('can:edit,user')->name('api.users.update');
    Route::delete('/{user}', 'Api\UserController@destroy')->middleware('can:delete,user')->name('api.users.delete');
});
