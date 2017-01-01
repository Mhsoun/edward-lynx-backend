<?php

// /user Endpoints
Route::group(['prefix' => 'user'], function() {
    Route::get('/', 'UserController@get');
    Route::patch('/', 'UserController@update');
});

Route::get('/surveys', 'SurveyController@index');
Route::post('/surveys', 'SurveyController@create')->middleware('can:create,App\Models\Survey');
Route::get('/surveys/{survey}', 'SurveyController@show')->middleware('can:view,survey');