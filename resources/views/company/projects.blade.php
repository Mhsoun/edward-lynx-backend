@extends('layouts.default')
@section('content')
    <h2 style="margin-top: 0px;">{{ Lang::get('surveys.activeSurveys') }}</h2>
    @include('survey.partials.list', ['surveys' => $activeSurveys, 'tableName' => 'activeSurveys'])

    <h2>{{ Lang::get('surveys.finishedSurveys') }}</h2>
    @include('survey.partials.list', ['surveys' => $finishedSurveys, 'tableName' => 'finishedSurveys'])

    <script src="//cdnjs.cloudflare.com/ajax/libs/list.js/1.1.1/list.min.js"></script>
    <script type="text/javascript" src="{{ asset('js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/bootstrap-datepicker.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/bootstrap-datetimepicker.min.css') }}">

    <a href="{{ action('CompanyController@index') }}">{{ Lang::get('buttons.back') }}</a>
@endsection
