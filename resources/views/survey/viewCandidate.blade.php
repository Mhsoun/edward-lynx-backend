@extends('layouts.default')

@section('content')
	<div class="mainBox">
		<link rel="stylesheet" href="{{ asset('css/bootstrap-datetimepicker.min.css') }}">
	    <h2>
	        {{ Lang::get('surveys.survey') }}: {{ $survey->name }}
	        ({{ $survey->typeName() }})
	    </h2>

	    <h3>{{ Lang::get('surveys.forCandidate') }} {{ $candidate->recipient->name }}</h3>

		@if (\App\SurveyTypes::isNewProgress($survey))
			{!! Form::open(['action' => ['SurveyUpdateController@updateSetCandidateEndDate', $survey->id], 'method' => 'put']) !!}
				<input type="hidden" name="candidateId" value="{{ $candidate->recipientId }}">

				@include('shared.dateTimePicker', [
					'name' => 'endDate',
					'label' => Lang::get('surveys.endDate'),
					'value' => $candidate->endDate ?: $survey->endDate
				])

				@include('shared.dateTimePicker', [
					'name' => 'endDateRecipients',
					'label' => Lang::get('surveys.endDate') . ' ' . Lang::get('surveys.participants'),
					'value' => $candidate->endDateRecipients ?: $survey->endDate
				])

				<button class="btn btn-primary" type="submit">{{ Lang::get('buttons.update') }}</button>
			{!! Form::close() !!}
		@endif

		@if ($survey->type == \App\SurveyTypes::Individual)
			{!! Form::open(['action' => ['SurveyUpdateController@updateSetCandidateEndDate', $survey->id], 'method' => 'put']) !!}
				<input type="hidden" name="candidateId" value="{{ $candidate->recipientId }}">

				@include('shared.dateTimePicker', [
					'name' => 'endDate',
					'label' => Lang::get('surveys.endDate'),
					'value' => $candidate->endDate ?: $survey->endDate
				])
				<button class="btn btn-primary" type="submit">{{ Lang::get('buttons.update') }}</button>
			{!! Form::close() !!}
		@endif

		@if (\App\SurveyTypes::isNewProgress($survey))
			@if ($candidate->userReport() == null && $candidate->invited()->where('recipientId', '!=', $candidate->recipientId)->count() >= 1)
				<br>
				{!! Form::open(['action' => ['SurveyUpdateController@createUserReport', 'id' => $survey->id, 'candidateId' => $candidate->recipientId], 'method' => 'put']) !!}
					<button class="btn btn-primary" type="submit">{{ Lang::get('surveys.createUserReport') }}</button>
				{!! Form::close() !!}
			@endif
		@endif

	    {!! Form::open(['action' => ['SurveyController@sendReminders', $survey->id], 'method' => 'put']) !!}
	        @include('survey.partials.listRecipients', [
	            'tableName' => 'candidate' . $candidate->recipientId . 'InvitedTable',
	            'recipients' => $candidate->invited()->get()])
	        <button class="btn btn-primary pull-right" type="submit">{{ Lang::get('buttons.send') }}</button>
	    {!! Form::close() !!}

	    <a href="{{ action('SurveyController@show', ['id' => $survey->id]) }}">{{ Lang::get('buttons.back') }}</a>
	</div>

	<script type="text/javascript" src="{{ asset('js/moment.min.js') }}"></script>
	<script type="text/javascript" src="{{ asset('js/bootstrap-datepicker.min.js') }}"></script>
@endsection
