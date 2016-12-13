<?php
    $invitedRecipients = $survey
        ->recipients()
        ->whereNotExists(function ($query)
        {
            $query
                ->from('survey_candidates')
                ->whereRaw('survey_candidates.recipientId = survey_recipients.recipientId');
        });

    $numInvited = $invitedRecipients->count();
    $numInvitedAnswered = $invitedRecipients->where('hasAnswered', '=', true)->count();

    function average($sum, $count)
    {
        if ($count == 0) {
            return 0;
        } else {
            return $sum / $count;
        }
    }
?>

@section('diagramContent')
    {{ Lang::get('report.numParticipantsInvited') }}: {{ $numInvited }}
    <br>
    {{ Lang::get('report.averageInvitesPerPerson') }}: {{ round(average($numInvited, $survey->candidates()->count()), 1) }}
    <br>
    {{ sprintf(Lang::get('report.ratioTookSurvey'), round(average($numInvitedAnswered, $numInvitedAnswered) * 100), $numInvited) }}
@overwrite

@include('survey.reportpages.diagram', [
    'diagramName' => 'groupSummary',
    'includeTitle' => Lang::get('report.groupSummary'),
    'titleText' => Lang::get('report.groupSummary'),
    'bodyText' => Lang::get('report.groupSummaryText'),
    'pageBreak' => true
])
