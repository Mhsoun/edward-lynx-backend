@extends('layouts.no-nav')
@section('content')
    <h3>{{ Lang::get('surveys.surveyAnsweredHeader') }}</h3>
    @if ($survey->thankYouText != "")
        <p>
            {!! \App\EmailContentParser::parse($survey->thankYouText, $parserData) !!}
        </p>
    @else
        {{ Lang::get('surveys.surveyAnsweredText') }}
    @endif
@stop
