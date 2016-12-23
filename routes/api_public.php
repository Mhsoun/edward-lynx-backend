<?php

/*
|--------------------------------------------------------------------------
| Public API routes
|--------------------------------------------------------------------------
|
| Define API routes that don't require authentication below.
|
*/

Route::post('/user/forgotpassword', 'UserController@forgotPassword');