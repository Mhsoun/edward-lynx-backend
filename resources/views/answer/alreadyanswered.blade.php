@extends('layouts.no-nav')
@section('content')
    <h3>{{ Lang::get('surveys.alreadyAnsweredHeader') }}</h3>
    {{ Lang::get('surveys.alreadyAnsweredContent') }}
@stop