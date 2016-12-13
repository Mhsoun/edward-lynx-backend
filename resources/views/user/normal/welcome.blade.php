@extends('layouts.default')
@section('content')
    <div class="container">
        <div class="mainBox">
            <h2>{{ sprintf(Lang::get('welcome.welcomeText'), Auth::user()->name) }}</h2>
            {!! Lang::get('welcome.infoText') !!}

            <br/>
            <h3>{{ Lang::get('welcome.activeProjects') }}</h3>
            {{ Lang::get('welcome.activeProjectsText') }}, <a href="{{ action('SurveyController@index') }}">{{ Lang::get('welcome.clickHere') }}</a>.
            <table class="table">
                <col style="width: 15%" />
                <col style="width: 65%" />
                <col style="width: 20%" />
                <tr>
                    <th>{{ Lang::get('surveys.type') }}</th>
                    <th>{{ Lang::get('surveys.name') }}</th>
                    <th></th>
                </tr>
                @foreach ($activeSurveys as $survey)
                    <tr>
                        <td>{{ $survey->typeName() }}</td>
                        <td><a href="{{ action('SurveyController@show', $survey->id) }}">{{ $survey->name }}</a></td>
                        <td>
                            <a class="btn btn-primary btn-xs" href="{{ action('SurveyController@edit', $survey->id) }}">
                                <span class="glyphicon glyphicon-pencil"></span>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@stop
