<?php

Route::get('/user', 'UserController@get');
Route::patch('/user', 'UserController@update');
Route::get('/user/test', 'UserController@testAuth')->middleware('auth:api');
Route::get('/user/header', 'UserController@testHeader');