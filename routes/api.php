<?php

// /user Endpoints
Route::group(['prefix' => 'user'], function() {
    Route::get('/', 'UserController@get');
    Route::patch('/', 'UserController@update');
    Route::post('/registration-tokens', 'UserController@registrationTokens');
});

// /users endpoint
Route::group(['prefix' => 'users'], function() {
    Route::get('/', 'UserController@index');
});

// /surveys Endpoints
Route::group(['prefix' => '/surveys'], function() {
    Route::get('/', 'SurveyController@index');
    Route::post('/', 'SurveyController@create');
    Route::get('/{survey}', 'SurveyController@show')
        ->middleware('can:view,survey')
        ->name('api1-survey');
    
    Route::patch('/{survey}', 'SurveyController@update')
        ->middleware('can:update,survey');
    
    Route::get('/{survey}/questions', 'SurveyController@questions')
        ->middleware('can:view,survey')
        ->name('api1-survey-questions');
    
    Route::post('/{survey}/answers', 'AnswerController@create');
});

// /instant-feedbacks Endpoints
Route::group(['prefix' => '/instant-feedbacks'], function() {
    Route::post('/', 'InstantFeedbackController@create');
    Route::get('/{instantFeedback}', 'InstantFeedbackController@show')
        ->middleware('can:view,instantFeedback')
        ->name('api1-instant-feedback');
});