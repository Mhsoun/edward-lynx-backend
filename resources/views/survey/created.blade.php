@extends('layouts.default')
@section('content')
	<div class="col-md-6">
		<h2>{{ Lang::get('surveys.created') }}</h2>
		<p>
			{{ Lang::get('surveys.createdText') }}
			@if ($survey->type == \App\SurveyTypes::Progress)
				{{ Lang::get('surveys.createdTextProgress') }}
			@endif
		</p>
		<a href="{{ action('SurveyController@show', ['id' => $survey->id]) }}">{{ ucfirst(Lang::get('buttons.clickHere')) }}</a> {{ Lang::get('surveys.toViewProject') }}
	</div>
@stop
