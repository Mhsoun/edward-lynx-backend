<?php namespace App;

use Mail;
use App\SurveyTypes;

/**
* Handles emailing for surveys
*/
class SurveyEmailer
{
    protected $sendSurveyEmail;

    /**
     * Create a new controller instance.
     */
    public function __construct(SendSurveyEmail $sendSurveyEmail)
    {
        $this->sendSurveyEmail = $sendSurveyEmail;
    }

    /**
    * Sends an email to the given recipient
    */
    public function sendMail($survey, $content, $recipient)
    {
        $invitedBy = $recipient->id;
        if (isset($recipient->invitedById)) {
            $invitedBy = $recipient->invitedById;
        }

        $data = [
            'surveyName' => $survey->name,
            'recipientName' => $recipient->name,
            'surveyLink' => $recipient->link,
            'surveyEndDate' => $survey->endDateFor($invitedBy, $recipient->id)->format('Y-m-d H:i'),
            'companyName' => $survey->owner->name
        ];

        if ($survey->type == SurveyTypes::Individual || $survey->type == SurveyTypes::Progress) {
            if (isset($recipient->invitedById)) {
                $data['toEvaluateName'] = $survey->candidates()
                    ->where('recipientId', '=', $recipient->invitedById)
                    ->first()
                    ->recipient->name;
            } else {
                $data['toEvaluateName'] = $recipient->name;
            }
        } else if (SurveyTypes::isGroupLike($survey->type)) {
            $data['toEvaluateGroupName'] = $survey->targetGroup->name;
            $data['toEvaluateRoleName'] = $survey->toEvaluateRole()->name;
        }

        $mailData = (object)[
            'text' => $content->text,
            'subject' => $content->subject,
            'data' => $data
        ];

        $this->sendSurveyEmail->send($mailData, $survey, $recipient);
    }

    /**
    * Creates the email data
    */
    private function createEmailData($survey, $recipient)
    {
        return (object)[
            'email' => $recipient->recipient->mail,
            'name' => $recipient->recipient->name,
            'invitedById' => $recipient->invitedById,
            'id' => $recipient->recipientId,
            'link' => str_replace('http://localhost', config('app.url'), action('AnswerController@show', ['link' => $recipient->link])),
        ];
    }

    /**
     * Sends the inivtation email to the given recipient
     */
    public function sendSurveyInvitation($survey, $recipient)
    {
        $inviteText = $survey->invitationText;
        if (SurveyTypes::isGroupLike($survey->type) && $survey->toEvaluateRole()->id == $recipient->roleId) {
            $inviteText = $survey->evaluatedTeamInvitationText;
        }

        if (SurveyTypes::isIndividualLike($survey->type)
            && $recipient->invitedById == $recipient->recipientId) {
            $inviteText = $survey->candidateInvitationText;
        }

        $this->sendMail(
            $survey,
            $inviteText,
            $this->createEmailData($survey, $recipient));
    }

    /**
     * Sends a reminding mail to the given recipient
     */
    public function sendReminder($survey, $recipient)
    {
        $this->sendMail(
            $survey,
            $survey->manualRemindingText,
            $this->createEmailData($survey, $recipient));
    }

    /**
     * Sends an invite others reminding mail to the given recipient
     */
    public function sendInviteOtherReminder($survey, $recipient, $inviteLink)
    {
        $this->sendMail(
            $survey,
            $survey->inviteOthersRemindingText,
            (object)[
                'email' => $recipient->recipient->mail,
                'name' => $recipient->recipient->name,
                'invitedById' => $recipient->invitedById,
                'id' => $recipient->recipientId,
                'link' => str_replace('http://localhost', config('app.url'), action('InviteController@show', ['link' => $inviteLink])),
            ]);
    }

    /**
    * Sends a email to evaluate the given recipient
    */
    public function sendToEvaluate($survey, $recipient, $inviteLink)
    {
        $this->sendMail($survey, $survey->toEvaluateText, (object)[
			'email' => $recipient->recipient->mail,
			'name' => $recipient->recipient->name,
			'invitedById' => $recipient->invitedById,
			'id' => $recipient->recipientId,
            'link' => str_replace('http://localhost', config('app.url'), action('InviteController@show', ['link' => $inviteLink])),
        ]);
    }

    /**
    * Sends the user report link
    */
    public function sendUserReportLink($survey, $recipient, $reportLink)
    {
        $this->sendMail($survey, $survey->userReportText, (object)[
			'email' => $recipient->recipient->mail,
			'name' => $recipient->recipient->name,
			'invitedById' => $recipient->invitedById,
			'id' => $recipient->recipientId,
            'link' => str_replace('http://localhost', config('app.url'), action('ReportController@showUserReport', ['link' => $reportLink])),
        ]);
    }
}
