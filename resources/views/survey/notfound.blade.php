@extends('layouts.default')
@section('content')
    <h3>{{ Lang::get('surveys.notFound') }}</h3>
    {{ Lang::get('surveys.notFoundContent') }}
    <br>
    <a href="{{ action('SurveyController@index') }}">{{ Lang::get('buttons.back') }}</a>
@stop
