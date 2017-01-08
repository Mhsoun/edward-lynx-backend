<?php

// /user Endpoints
Route::group(['prefix' => 'user'], function() {
    Route::get('/', 'UserController@get');
    Route::patch('/', 'UserController@update');
    Route::post('/registration-tokens', 'UserController@registrationTokens');
});

// /surveys Endpoints
Route::group(['prefix' => '/surveys'], function() {
    Route::get('/', 'SurveyController@index');
    Route::post('/', 'SurveyController@create');
    Route::get('/{survey}', 'SurveyController@show')
        ->middleware('can:view,survey')
        ->name('api1-survey');
    
    Route::get('/{survey}/questions', 'SurveyController@questions')
        ->middleware('can:view,survey')
        ->name('api1-survey-questions');
});