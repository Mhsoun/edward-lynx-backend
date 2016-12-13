<div class="tab-pane" id="candidates">
    {!! Form::open(['action' => ['SurveyUpdateController@updateAddCandidate', $survey->id], 'method' => 'put']) !!}
        @include('survey.partials.addRecipient', [
            'newRecipientHeader' => Lang::get('surveys.createNewRecipient'),
            'existingRecipients' => $ownerRecipients,
            'isProgressCandidate' => \App\SurveyTypes::isNewProgress($survey),
            'is360Candidate' => $survey->type == \App\SurveyTypes::Individual,
        ])
    {!! Form::close() !!}
</div>
