<?php namespace App\Models;

use App\EmailContentParser;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
* Represents a recipient in a survey
*/
class SurveyRecipient extends Model
{
    const NO_ANSWERS = 0;
    const PENDING_ANSWERS = 1;
    const COMPLETE_ANSWERS = 2;
    const NO_INVITE = 3;
    
    /**
    * The database table used by the model
    */
    protected $table = 'survey_recipients';

    //Laravel does not support composite primary key, we use a the link as primary here so a recipient can be updated.
    protected $primaryKey = 'link';
    public $incrementing = false;

    protected $fillable = [];
    public $timestamps = false;

    protected $dates = ['lastReminder'];
    
    /**
     * Returns the status of a recipient for a given survey.
     *
     * @param   App\Models\Survey       $survey
     * @param   App\Models\Recipient    $recipient
     * @return  int
     */
    public static function surveyStatus(Survey $survey, Recipient $recipient)
    {
        $surveyRecipient = $survey->recipients()
                                  ->where('recipientId', $recipient->id)
                                  ->first();
        
        // If we can't find an invite, then the user is not
        // invited to answer the survey.
        if (!$surveyRecipient) {
            return self::NO_INVITE;
        }
        
        // If this invite has been marked answered then it is complete.
        if ($surveyRecipient->hasAnswered) {
            return self::COMPLETE_ANSWERS;
        }
        
        if ($surveyRecipient->answers()->count() > 0) {
            return self::PENDING_ANSWERS;
        } else {
            return self::NO_ANSWERS;
        }
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
        
        return $survey->recipients()
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
    public static function userIsValidRecipient(Survey $survey, User $user, $key)
    {
        return self::findForUser($survey, $user, $key) != null;
    }

    /**
     * Returns recipients that should be answered by the provided user.
     * 
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param  App\Models\User                      $user
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeAnswerableBy(Builder $query, User $user)
    {
        $ids = Recipient::recipientIdsOfUser($user);
        return $query->whereIn('recipientId', $ids);
    }

    /**
     * Scopes unanswered invites.
     * 
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param  App\Models\User                      $user
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnanswered(Builder $query)
    {
        return $query->where('hasAnswered', false);
    }

    /**
    * Returns the survey that the recipient belongs to
    */
    public function survey()
    {
        return $this->belongsTo('\App\Models\Survey', 'surveyId');
    }

    /**
    * Returns the recipient object
    */
    public function recipient()
    {
        return $this->belongsTo(Recipient::class, 'recipientId');
    }

    /**
    * Returns the invited by object
    */
    public function invitedByObj()
    {
        return $this->belongsTo('\App\Models\Recipient', 'invitedById');
    }
    
    /**
     * Returns the recipient's answers to this survey.
     *
     * @return  Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function answers()
    {
        return $this->survey->answers()->where('answeredById', $this->recipientId);
    }

    /**
    * Indicates if the current recipient is a candidate
    */
    public function isCandidate()
    {
        if ($this->recipientId != $this->invitedById) {
            return false;
        }

        return $this->survey
            ->candidates()
            ->where('recipientId', '=', $this->recipientId)
            ->count() > 0;
    }

    /**
    * If this recipient is a candidate, return the candidate
    */
    public function candidate()
    {
        if ($this->recipientId != $this->invitedById) {
            return null;
        }

        return $this->survey
            ->candidates()
            ->where('recipientId', '=', $this->recipientId)
            ->first();
    }

    /**
    * Returns the candidate that invited this recipient
    */
    public function invitedByCandidate()
    {
        return $this->survey
            ->candidates()
            ->where('recipientId', '=', $this->invitedById)
            ->first();
    }

    public function generateDescription($desc)
    {
        $survey = $this->survey;
        
        if ($this->invitedById == 0) {
            $toEvaluate = $this->recipient;
        } else {
            $toEvaluate = $this->invitedByObj;
        }

        $data = [
            'surveyName'        => $survey->name,
            'surveyLink'        => route('survey.answer', $survey),
            'surveyEndDate'     => $survey->endDate->format('Y-m-d H:i'),
            'recipientName'     => $this->recipient->name,
            'companyName'       => $survey->owner->parentId === null ? $survey->owner->name : $survey->owner->company->name,
            'toEvaluateName'    => $toEvaluate->name
        ];

        $desc = EmailContentParser::parse($desc, $data);
        $desc = strip_tags($desc);
        return $desc;
    }

    /**
     * Returns the answer status of this survey invitation.
     *
     * @return integer
     */
    public function answerStatus()
    {
        // Catch survey invites where the invitation is 0.
        $invitedBy = $this->invitedById ? $this->invitedById : $this->recipientId;

        if ($this->hasAnswered) {
            return self::COMPLETE_ANSWERS;
        }

        if ($this->answers()->where('invitedById', $invitedBy)->count() > 0) {
            return self::PENDING_ANSWERS;
        } else {
            return self::NO_ANSWERS;
        }
    }
    
    /**
     * Returns the JSON representation of this model.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  array
     */
    public function jsonSerialize()
    {
        $json = [
            'key'       => $this->link,
            'final'     => $this->hasAnswered ? true : false,
            'answers'   => $this->answers
        ];
        
        return $json;
    }
}
