@extends('layouts.no-nav')
@section('content')
    <h3>{{ Lang::get('surveys.notStartedHeader') }}</h3>
    {{ sprintf(Lang::get('surveys.notStartedContent'), $survey->startDate, $survey->startDate->diffInDays()) }}
@stop