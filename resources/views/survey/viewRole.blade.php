@extends('layouts.default')

@section('content')
    <div class="mainBox">
        <h2>
            {{ Lang::get('surveys.survey') }}: {{ $survey->name }}
            ({{ $survey->typeName() }})
        </h2>

        @if ($roleGroup->toEvaluate)
            <h3>{{ Lang::get('surveys.toEvaluateRoleHeader') }} - {{ $roleGroup->name }}</h3>
        @else
            <h3>{{ Lang::get('surveys.evaluatingRoleHeader') }} - {{ $roleGroup->name }}</h3>
        @endif

        {!! Form::open(['action' => ['SurveyController@sendReminders', $survey->id], 'method' => 'put']) !!}
            @include('survey.partials.listRecipients', [
                'tableName' => 'candidate' . $roleGroup->id . 'InvitedTable',
                'recipients' => $roleGroup->members])
            <button class="btn btn-primary pull-right" type="submit">{{ Lang::get('buttons.send') }}</button>
        {!! Form::close() !!}

        <a href="{{ action('SurveyController@show', ['id' => $survey->id]) }}">{{ Lang::get('buttons.back') }}</a>
    </div>
@endsection
