<?php namespace App;

/**
* Represents an interface for sending survey emails
*/
interface SendSurveyEmail
{
    /**
    * Sends the given email
    */
    public function send($mailData, $survey, $recipient);
}
