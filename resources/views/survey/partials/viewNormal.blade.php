<h3>{{ Lang::get('surveys.participants') }}</h3>

{!! Form::open(['action' => ['SurveyController@sendReminders', $survey->id], 'method' => 'put']) !!}
    @include('survey.partials.listRecipients', [
        'tableName' => 'invitedTable',
        'recipients' => $survey->recipients()->get()])
    <button class="btn btn-primary pull-right" type="submit">{{ Lang::get('buttons.send') }}</button>
{!! Form::close() !!}
