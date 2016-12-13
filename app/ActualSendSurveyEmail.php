<?php namespace App;
use Mail;

/**
* An actual implementation of the SendSurveyEmail interface
*/
class ActualSendSurveyEmail implements SendSurveyEmail
{
    /**
    * Sends the given email
    */
    public function send($mailData, $survey, $recipient)
    {
        Mail::queue('emails.surveyTemplate', (array)$mailData, function ($message) use ($mailData, $survey, $recipient) {
            $message
                ->to($recipient->email, $recipient->name)
                // ->from("noreply@lynxtool.edwardlynx.com", $survey->owner->name)
                ->from("lynx.tool@edwardlynx.com", $survey->owner->name)
                ->subject(\App\EmailContentParser::parse($mailData->subject, $mailData->data, true));

            //Add headers if the mail fails and we need the survey id
            $message->getHeaders()->addTextHeader(
                'X-Mailgun-Variables',
                json_encode(['surveyId' => $survey->id, 'recipientId' => $recipient->id]));
        });
    }
}
?>
