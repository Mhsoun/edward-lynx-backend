<?php

// /user Endpoints
Route::group(['prefix' => 'user'], function() {
    Route::get('/', 'UserController@get');
    Route::patch('/', 'UserController@update');
    Route::post('/devices', 'UserController@registerDevice');
});

// /users endpoint
Route::group(['prefix' => 'users'], function() {
    Route::get('/', 'UserController@index');
    Route::get('/{user}', 'UserController@show')
        ->middleware('can:view,user')
        ->name('api1-user');
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
    
    Route::get('/{survey}/answers', 'AnswerController@index')
        ->middleware('can:answer,survey')
        ->name('api1-survey-answers');
    Route::post('/{survey}/answers', 'AnswerController@answer')
        ->middleware('can:answer,survey');
    Route::get('/{survey}/results', 'AnswerController@results')
        ->middleware('can:viewAnswers,survey')
        ->name('ap1-survey-results');
});

// /instant-feedbacks Endpoints
Route::group(['prefix' => '/instant-feedbacks'], function() {
    Route::get('/', 'InstantFeedbackController@index');
    Route::post('/', 'InstantFeedbackController@create');
    
    Route::get('/{instantFeedback}', 'InstantFeedbackController@show')
        ->middleware('can:view,instantFeedback')
        ->name('api1-instant-feedback');
    Route::patch('/{instantFeedback}', 'InstantFeedbackController@update')
        ->middleware('can:update,instantFeedback');
    
    Route::get('/{instantFeedback}/answers', 'InstantFeedbackController@answers')
        ->middleware('can:viewAnswers,instantFeedback')
        ->name('api1-instant-feedback-answers');
    Route::post('/{instantFeedback}/answers', 'InstantFeedbackController@answer')
        ->middleware('can:answer,instantFeedback')
        ->name('api1-answer-instant-feedback');
    
    Route::post('/{instantFeedback}/shares', 'InstantFeedbackController@share')
        ->middleware('can:share,instantFeedback')
        ->name('api1-instant-feedback-share');
});

// /dev-plans Endpoints
Route::group(['prefix' => '/dev-plans'], function() {
    Route::get('/', 'DevelopmentPlanController@index');
    Route::post('/', 'DevelopmentPlanController@create');
    Route::get('/{devPlan}', 'DevelopmentPlanController@show')
        ->middleware('can:view,devPlan')
        ->name('api1-dev-plan');
    
    Route::patch('/{devPlan}/goals/{goal}', 'DevelopmentPlanController@updateGoal')
        ->middleware('can:update,devPlan');
    Route::delete('/{devPlan}/goals/{goal}', 'DevelopmentPlanController@deleteGoal')
        ->middleware('can:update,devPlan');
    
    Route::patch('/{devPlan}/goals/{goal}/actions/{action}', 'DevelopmentPlanController@updateGoalAction')
        ->middleware('can:update,devPlan');
});

// /categories Endpoints
Route::group(['prefix' => '/categories'], function() {
    Route::get('/', 'CategoryController@index')
        ->name('api1-categories');
    Route::get('/{category}', 'CategoryController@show')
        ->middleware('can:view,category')
        ->name('api1-category');
});