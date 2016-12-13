<?php namespace App;

/**
* An dummy implementation of the SendSurveyEmail interface
*/
class DummySendSurveyEmail implements SendSurveyEmail
{
    public $sentEmails = [];

    /**
    * Sends the given email
    */
    public function send($mailData, $survey, $recipient)
    {
        array_push($this->sentEmails, (object)[
            'mailData' => $mailData,
            'survey' => $survey,
            'recipient' => $recipient,
        ]);
    }
}
?>
