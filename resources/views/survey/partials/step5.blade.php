<h2>{{ Lang::get('surveys.step6InfoText') }}</h2>
<div id="toEvaluateEmailBox">
    @include('survey.partials.editEmail', [
        'header' => Lang::get('surveys.toEvaluateEmail'),
        'name' => 'toEvaluateInvitation'
    ])

    <div id="candidateAnswerEmailBox">
        @include('survey.partials.editEmail', [
            'header' => Lang::get('surveys.candidateInvitationEmail'),
            'name' => 'candidateInvitation'
        ])
    </div>

    <div id="userReportEmailBox">
        @include('survey.partials.editEmail', [
            'header' => Lang::get('surveys.userReportEmail'),
            'name' => 'userReport'
        ])
    </div>
</div>

<div id="toEvaluateTeamBox">
    @include('survey.partials.editEmail', [
        'header' => Lang::get('surveys.teamInvitationEmail'),
        'name' => 'toEvaluateTeamInvitation'
    ])
</div>

@include('survey.partials.editEmail', [
    'header' => Lang::get('surveys.invitationEmail'),
    'name' => 'invitation'
])

@include('survey.partials.editEmail', [
    'header' => Lang::get('surveys.remindingEmail'),
    'name' => 'reminder'
])

<div id="inviteOthersReminderEmailBox">
    @include('survey.partials.editEmail', [
        'header' => Lang::get('surveys.inviteRemindingMail'),
        'name' => 'inviteOthersReminder'
    ])
</div>

<button id="step5NextBtn" class="btn btn-primary nextBtn btn-lg pull-right" type="button">{{ Lang::get('buttons.next') }}</button>

<script type="text/javascript" src="{{ asset('js/survey.step5.js') }}"></script>
<?php
    $languages = ['en', 'sv', 'fi'];
    $defaultTexts = [];

    foreach ($languages as $lang) {
        $typeTexts = [];

        foreach (\App\Models\DefaultText::defaultEmails(Auth::user()) as $surveyType) {
            $emails = [];

            foreach ($surveyType->emails as $defaultEmail) {
                $getEmail = $defaultEmail->getEmail;
                $email = $getEmail($surveyType->id, $lang);
                array_push($emails, (object)[
                    'subject' => $email->subject,
                    'message' => $email->message
                ]);
            }

            $typeTexts[$surveyType->id] = $emails;
        }

        $defaultTexts[$lang] = $typeTexts;
    }
?>
<script type="text/javascript">
    SurveyStep5.setDefaultEmails({!! json_encode($defaultTexts) !!});
</script>
