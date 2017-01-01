<?php

Route::get('/user', 'UserController@get');
Route::patch('/user', 'UserController@update');

// /surveys Endpoints
Route::group(['prefix' => '/surveys'], function() {
    Route::get('/', 'SurveyController@index');
    Route::post('/', 'SurveyController@create');
    Route::get('/surveys/{survey}', 'SurveyController@show')
        ->middleware('can:view,survey')
        ->name('api1-survey');
});