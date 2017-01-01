<?php

Route::get('/user', 'UserController@get');
Route::patch('/user', 'UserController@update');

Route::get('/surveys', 'SurveyController@index');
Route::post('/surveys', 'SurveyController@create');
Route::get('/surveys/{survey}', 'SurveyController@show')->middleware('can:view,survey');