@extends('layouts.default')
@section('content')
    <div class="container">
        <div class="mainBox">
            <h2>{{ Lang::get('welcome.welcome') }} {{ Auth::user()->name }}</h2>
            <h4>{{ Lang::get('welcome.companyWelcomeText') }}, <a href="{{ action('CompanyController@index') }}">{{ Lang::get('welcome.clickHere') }}</a>.</h4>
            <h4>{{ Lang::get('welcome.viewProjectText') }}, <a href="{{ action('SurveyController@index') }}">{{ Lang::get('welcome.clickHere') }}</a>.</h4>
            <h4>{{ Lang::get('welcome.newProjectText') }}, <a href="{{ action('SurveyController@create') }}">{{ Lang::get('welcome.clickHere') }}</a>.</h4>
            <h4>{{ Lang::get('welcome.systemPerformanceText') }}, <a href="{{ action('AdminController@performanceIndex') }}">{{ Lang::get('welcome.clickHere') }}</a>.</h4>
            <h4>{{ Lang::get('welcome.newRolesText') }}, <a href="{{ action('AdminController@rolesIndex') }}">{{ Lang::get('welcome.clickHere') }}</a>.</h4>
        </div>
    </div>
@stop
