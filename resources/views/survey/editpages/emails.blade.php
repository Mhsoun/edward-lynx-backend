<div class="tab-pane" id="emails">
    {!! Form::open(['action' => ['SurveyUpdateController@updateEmails', $survey->id], 'method' => 'put']) !!}
        @if (\App\SurveyTypes::isIndividualLike($survey->type))
            @include('survey.partials.editEmail', [
                'header' => Lang::get('surveys.toEvaluateEmail'),
                'name' => 'toEvaluateInvitation',
                'emailText' => $survey->toEvaluateText
            ])

            @if ($survey->type != \App\SurveyTypes::Progress)
                @include('survey.partials.editEmail', [
                    'header' => Lang::get('surveys.candidateInvitationEmail'),
                    'name' => 'candidateInvitation',
                    'emailText' => $survey->candidateInvitationText
                ])
            @else
                @include('survey.partials.editEmail', [
                    'header' => Lang::get('surveys.userReportEmail'),
                    'name' => 'userReport',
                    'emailText' => $survey->userReportText
                ])
            @endif
        @endif

        @if (\App\SurveyTypes::isGroupLike($survey->type))
            @include('survey.partials.editEmail', [
                'header' => Lang::get('surveys.teamInvitationEmail'),
                'name' => 'toEvaluateTeamInvitation',
                'emailText' => $survey->evaluatedTeamInvitationText
            ])
        @endif

        @include('survey.partials.editEmail', [
            'header' => Lang::get('surveys.invitationEmail'),
            'name' => 'invitation',
            'emailText' => $survey->invitationText
        ])

        @include('survey.partials.editEmail', [
            'header' => Lang::get('surveys.remindingEmail'),
            'name' => 'reminder',
            'emailText' => $survey->manualRemindingText
        ])

        @if (\App\SurveyTypes::isIndividualLike($survey->type))
            @include('survey.partials.editEmail', [
                'header' => Lang::get('surveys.inviteRemindingMail'),
                'name' => 'inviteOthersReminder',
                'emailText' => $survey->inviteOthersRemindingText
            ])
        @endif

       <button class="btn btn-primary" type="submit">{{ Lang::get('buttons.update') }}</button>
    {!! Form::close() !!}
</div>
