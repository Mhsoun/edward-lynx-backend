<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a candidate for a survey
*/
class SurveyCandidate extends Model
{
    /**
     * The database table used by the model
     */
    protected $table = 'survey_candidates';

    //Laravel does not support composite primary key, we use a the link as primary here so a recipient has be updated.
    protected $primaryKey = 'link';
	public $incrementing = false;

    protected $fillable = [];
    public $timestamps = false;

	protected $dates = ['endDate', 'endDateRecipients'];

    /**
     * Returns the survey that the recipient belongs to
     */
    public function survey()
    {
        return $this->belongsTo('\App\Models\Survey', 'surveyId');
    }

    /**
    * Returns the recipient
    */
    public function recipient()
    {
        return $this->belongsTo(Recipient::class, 'recipientId');
    }

    /**
    * Returns the survey recipient
    */
    public function surveyRecipient()
    {
        return $this->survey
            ->recipients()
            ->where('recipientId', '=', $this->recipientId)
            ->where('invitedById', '=', $this->recipientId)
            ->first();
    }

    /**
    * Indicates if the candidate exists.
    */
    public function exists()
    {
        return $this->survey->recipients()
            ->where('recipientId', '=', $this->recipientId)
            ->first() != null;
    }

    /**
    * Indicates if the candidate has answered
    */
    public function hasAnswered()
    {
        $recipient = $this->survey->recipients()
            ->where('recipientId', '=', $this->recipientId)
            ->where('invitedById', '=', $this->recipientId)
            ->first();

        if ($recipient == null) {
            return false;
        }

        return $recipient->hasAnswered;
    }

    /**
    * Returns the recipient that has been invited by this candidate
    */
    public function invited()
    {
        return $this->survey
            ->recipients()
            ->where('invitedById', '=', $this->recipientId);
    }

    /**
    * Returns data about how many has been invited and answered
    */
    public function invitedAndAnswered()
    {
        $toEvaluateRecipients = $this->survey->recipients()
            ->where('invitedById', '=', $this->recipientId)
            ->where('recipientId', '!=', $this->recipientId);

        $numInvited = $toEvaluateRecipients->count();

        $numAnswered = $toEvaluateRecipients
            ->where('hasAnswered', '=', true)
            ->count();

        return (object)[
            'invited' => $numInvited,
            'answered' => $numAnswered
        ];
    }

    /**
    * Returns the user report for the given candidate
    */
    public function userReport()
    {
        return $this->survey
            ->userReports()
            ->where('recipientId', '=', $this->recipientId)
            ->first();
    }
}
