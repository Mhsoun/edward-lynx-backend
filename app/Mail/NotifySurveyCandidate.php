<?php

namespace App\Mail;

use App\Models\Survey;
use App\EmailContentParser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySurveyCandidate extends Mailable
{
	use Queueable, SerializesModels;
	
	/**
	 * The Survey.
	 *
	 * @var App\Models\Survey
	 */
	public $survey;
	
    /**
     * Create a new message instance.
     *
	 * @param App\Models\Survey $survey
     * @return void
     */
    public function __construct(Survey $survey)
    {
        $this->survey = $survey;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
		$email = $this->survey->toEvaluateText();
		$subject = EmailContentParser::parse($email->subject, $survey);
		$body = EmailContentParser::parse($email->text, $survey);
		
        return $this->subject($subject)
					->view('emails.survey.notifyCandidate')
					->with([
						'body' => $body
					]);
    }
}
