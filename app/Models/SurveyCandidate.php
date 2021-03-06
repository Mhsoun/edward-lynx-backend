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
     * Returns TRUE if the provided email is a candidate of a survey.
     * 
     * @param   App\Models\Survey   $survey
     * @param   string              $email
     * @return  boolean
     */
    public static function isCandidateOf(Survey $survey, $email)
    {
        $recipients = Recipient::where('mail', $email)
                        ->get()
                        ->map(function($item) {
                            return $item->id;
                        })
                        ->toArray();

        return $survey->candidates()->whereIn('recipientId', $recipients)->count() > 0;
    }

    /**
     * Returns the first survey candidate record with the same email as the provided user.
     *
     * @param App\Models\Survey $survey
     * @param App\Models\User $user
     * @param string $key
     * @return App\Models\SurveyCandidate
     */
    public static function findForUser(Survey $survey, User $user, $key)
    {
        $recipientIds = Recipient::recipientIdsOfUser($user);
        
        return $survey->candidates()
            ->whereIn('recipientId', $recipientIds)
            ->where('link', $key)
            ->first();
    }

    /**
     * Returns TRUE if the provided user with a given key is a valid candidate
     * of the provided survey.
     *
     * @param App\Models\Survey $survey
     * @param App\Models\User $user
     * @param string $key
     * @return bool
     */
    public static function userIsValidCandidate(Survey $survey, User $user, $key)
    {
        return self::findForUser($survey, $user, $key) != null;
    }

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
