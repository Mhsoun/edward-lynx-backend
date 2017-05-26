<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Auth::routes();

Route::bind('form', function ($id) {
    return App\form::whereSlug($id)->first();
});

/* Use User model in the UserController*/
Route::resource('user', 'UserController');

// login required
Route::group(['middleware' => 'auth'], function () {
    /* Route for login page, company or super user*/
    Route::get('/', 'UserController@login');

    /* Route for page you see when logged in as a company*/
    Route::get('/home', 'UserController@index');

    /* Route for settings page for a user*/
    Route::get('/settings', 'UserController@settings');
    Route::put('/settings/password', 'UserController@updatePassword');
    Route::put('/settings/language', 'UserController@updateLanguage');
    Route::put('/settings/emails', 'UserController@updateEmails');
    Route::put('/settings/informations', 'UserController@updateInformations');
    Route::put('/settings/report-texts', 'UserController@updateReportTexts');
    Route::put('/settings/default-texts', 'UserController@updateDefaultTexts');
    Route::post('/settings/save-file/{id}', 'UserController@uploadFile');
    Route::post('/settings/save-color/{id}', 'UserController@saveColor');

    /* Route for first page for creating a survey*/
    Route::get('/create-survey', 'SurveyController@index');

    /* Routes for admins */
    Route::group(['middleware' => 'admin'], function() {
        Route::get('/company', 'CompanyController@index');
        Route::get('/company/create', 'CompanyController@create');
        Route::post('/company/create', 'CompanyController@store');

        Route::get('/company/{id}/edit', 'CompanyController@edit')
            ->name('companies.edit');
        Route::get('/company/{id}/reset-logo', 'CompanyController@resetLogo');
        Route::get('/company/{id}/delete', 'CompanyController@destroy');
        Route::get('/company/{id}/projects', 'CompanyController@viewProjects');
        Route::get('/company/{id}/reset-password', 'CompanyController@resetPasswordView');
        Route::put('/company/{id}/reset-password', 'CompanyController@resetPassword');
        Route::put('/company/{id}', 'CompanyController@update');

        Route::get('/system-performance', 'AdminController@performanceIndex');
        Route::get('/roles', 'AdminController@rolesIndex');
        Route::post('/roles', 'AdminController@createRole');
        Route::put('/roles', 'AdminController@updateRole');

        Route::get('/survey/{id}/random-answers', 'AnswerController@generateRandomAnswers');

        Route::get('/language/export', 'AdminController@exportLanguageStrings');

        Route::resource('users', 'UsersController', [ 'except' => [
            'show'
        ]]);
    });

    /* Route for index page for groups */
    Route::get('/group', 'GroupController@index');

    /* Routes for creating subcompanies */
    Route::post('/subcompany', 'GroupController@storeSubcompany');

    /* Routes for creating a group */
    Route::post('/group', 'GroupController@store');

    Route::group(['middleware' => 'group-owner'], function () {
        /* Routes for editing a group */
        Route::put('/group/{id}', 'GroupController@update');

        /* Routes for group members */
        Route::post('/group/{id}/member', 'GroupController@storeMember');
        Route::post('/group/{id}/import-members', 'GroupController@importMembers');

        Route::put('/group/{id}/member', 'GroupController@updateMember');
        Route::delete('/group/{id}/member', 'GroupController@destroyMember');

        /* Routes for deleting a group */
        Route::delete('/group/{id}/delete', "GroupController@destroy");
    });

    /* Routes for surveys */
    Route::get('/survey/not-found', 'SurveyController@notFound');

    Route::get('/survey', 'SurveyController@index');
    Route::get('/survey/create', 'SurveyController@create');
    Route::post('/survey/create', 'SurveyController@store');

    /* Routes for generating reports */
    Route::group(['middleware' => 'survey-owner'], function() {
        Route::get('/survey/{id}/report', 'ReportController@showReport');
    });

    Route::post('/survey/generate-report', 'ReportController@createPDF');
    Route::get('/survey/view-report/{id}', 'ReportController@viewReport');

    /* Report templates */
    Route::get('/report-template', 'ReportTemplateController@index');
    Route::get('/report-template/create', 'ReportTemplateController@create');
    Route::post('/report-template', 'ReportTemplateController@store');

    Route::get('/report-template/{id}/edit', 'ReportTemplateController@edit');
    Route::put('/report-template/{id}/edit', 'ReportTemplateController@update');
    Route::get('/report-template/{id}/delete', 'ReportTemplateController@delete');

    /* Routes for exporting data */
    Route::group(['middleware' => 'survey-owner'], function() {
        Route::get('/survey/{id}/export-csv', 'SurveyExportController@exportCSV');
        Route::get('/survey/{id}/export-excel', 'SurveyExportController@exportExcel');
    });

    Route::group(['middleware' => 'admin'], function() {
        Route::get('/survey/create/{company}', 'SurveyController@createCompany');
        Route::get('/survey/create-search', 'SurveyController@createCompanyByName');
    });

    /* Routes for questions & categories */
    Route::post('/survey/question', 'QuestionController@createQuestion');
    Route::put('/survey/question', 'QuestionController@updateQuestion');
    Route::delete('/survey/question', 'QuestionController@destroyQuestion');

    Route::post('/survey/question/category', 'QuestionController@createCategory');
    Route::put('/survey/question/category', 'QuestionController@updateCategory');
    Route::delete('/survey/question/category', 'QuestionController@destroyCategory');

    Route::group(['middleware' => 'survey-owner'], function() {
        /* Viewing a survey */
        Route::get('/survey/{id}', 'SurveyController@show')->where('id', '[0-9]+');

        Route::get('/survey/{id}/show-answers', 'SurveyController@showAnswers');
        Route::get('/survey/{id}/show-candidate', 'SurveyController@showCandidate');
        Route::get('/survey/{id}/show-role', 'SurveyController@showRole');

        Route::put('/survey/{id}/reminder', 'SurveyController@sendReminders');
        Route::put('/survey/{id}/invite-others-reminder', 'SurveyController@sendInviteOthersReminders');

        Route::delete('/survey/{id}', 'SurveyController@destroy');

        /* Routes for editing a survey */
        Route::get('/survey/{id}/edit', 'SurveyController@edit');

        /* Routes for updating a survey */
        Route::put('/survey/{id}/autoreminder', 'SurveyUpdateController@updateAutoReminder');
        Route::put('/survey/{id}/update-general', 'SurveyUpdateController@updateGeneral');
        Route::put('/survey/{id}/update-emails', 'SurveyUpdateController@updateEmails');
        Route::put('/survey/{id}/update-editparticipants', 'SurveyUpdateController@updateEditParticipants');
        Route::put('/survey/{id}/update-changerole', 'SurveyUpdateController@updateChangeRole');
        Route::get('/survey/{id}/update-deleteparticipant', 'SurveyUpdateController@updateDeleteParticipant');
        Route::put('/survey/{id}/update-addcandidate', 'SurveyUpdateController@updateAddCandidate');
        Route::put('/survey/{id}/update-addparticipants', 'SurveyUpdateController@updateAddParticipants');
        Route::put('/survey/{id}/update-addparticipant', 'SurveyUpdateController@updateAddParticipant');
        Route::put('/survey/{id}/update-addquestion', 'SurveyUpdateController@updateAddQuestion');
        Route::put('/survey/{id}/update-addcategory', 'SurveyUpdateController@updateAddCategory');
        Route::put('/survey/{id}/update-addexistingcategory', 'SurveyUpdateController@updateAddExistingCategory');
        Route::delete('/survey/{id}/update-delete-question', 'SurveyUpdateController@updateDeleteQuestion');
        Route::put('/survey/{id}/update-changequestionorder', 'SurveyUpdateController@updateQuestionOrder');
        Route::put('/survey/{id}/update-changecategoryorder', 'SurveyUpdateController@updateCategoryOrder');
        Route::get('/survey/{id}/update-deleteanswers', 'SurveyUpdateController@updateDeleteAnswers');
        Route::put('/survey/{id}/update-changecategorytitle', 'SurveyUpdateController@updateChangeCategoryTitle');
        Route::put('/survey/{id}/update-set-report-template', 'SurveyUpdateController@updateSetReportTemplate');
        Route::put('/survey/{id}/update-candidate-enddate', 'SurveyUpdateController@updateSetCandidateEndDate');
        Route::put('/survey/{id}/create-user-report', 'SurveyUpdateController@createUserReport');

        /* Creating comparision surveys */
        Route::get('/survey/create-comparison/{id}', 'SurveyController@createComparisonView');
        Route::post('/survey/create-comparison/{id}', 'SurveyController@createComparison');
    });

    Route::post('/survey/import-recipients', 'SurveyController@importRecipientsFromCSV');

    /* Routes for extra questions */
    Route::post('/survey/extra-questions', 'ExtraQuestionController@store');
});

/* Executes the auto reminder functionality. */
Route::get('/execute-progress-user-report', 'SurveyController@executeCreateUserReportLink');

/* Executes the auto reminder functionality. */
Route::get('/execute-autoreminders', 'SurveyController@executeAutoReminders');

/* Marks that an email bounced */
Route::post('/email-bounced', 'SurveyController@emailBounced');

/* Routes for setting up a password */
Route::get('/setup-password/{token}', 'UserController@setupPasswordView');
Route::post('/setup-password/{token}', 'UserController@setupPassword');

/* Routes for answering a survey */
Route::get('/survey/answer/{link}', 'AnswerController@show')
    ->name('survey.answer');
Route::post('/survey/answer/{link}', 'AnswerController@store');

/* Routes for inviting to a survey */
Route::post('/survey-invite/{link}/recipient', 'InviteController@addRecipient');
Route::delete('/survey-invite/{link}/recipient', 'InviteController@deleteRecipient');
Route::get('/survey-invite/{link}', 'InviteController@show');

/* User reports */
Route::get('/survey/user-report', 'ReportController@showUserReport');
Route::post('/survey/generate-user-report', 'ReportController@createUserPDF');

/* Controller for Auth*/
// Route::controllers([
//     'auth' => 'Auth\AuthController',
//     'password' => 'Auth\PasswordController',
// ]);
