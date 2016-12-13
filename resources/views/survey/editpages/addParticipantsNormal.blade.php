<div class="tab-pane" id="participants">
    {!! Form::open(['action' => ['SurveyUpdateController@updateAddParticipant', $survey->id], 'method' => 'put']) !!}
        @include('survey.partials.addRecipient', [
            'newRecipientHeader' => Lang::get('groups.newMemberCreateNew'),
            'existingRecipients' => $ownerRecipients,
            'ignorePosition' => true
        ])
    {!! Form::close() !!}
</div>
