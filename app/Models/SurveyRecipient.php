<?php namespace App\Models;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;

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
     * @param   App\Models\User|array   $user
     * @return  int
     */
    public static function surveyStatus(Survey $survey, $user)
    {
        if ($user instanceof User) {
            $recipient = $survey->recipients()
                                ->where([
                                    'recipientId'   => $user->id,
                                    'recipientType' => 'users'
                                ])->first();
        } elseif (is_array($user)) {
            // Make sure we have the correct keys.
            if (empty($user['name']) || empty($user['email'])) {
                throw new InvalidArgumentException('Missing user name and email details.');
            }
            
            $recipientRecord = Recipient::where([
                'ownerId'   => $survey->ownerId,
                'name'      => $user['name'],
                'mail'      => $user['email']
            ]);
            $recipient = $survey->recipients()
                                ->where([
                                    'recipientId'   => $recipientRecord->id,
                                    'recipientType' => 'recipients'
                                ])->first();
        }
        
        // If we can't find an invite, then the user is not
        // invited to answer the survey.
        if (!$recipient) {
            return self::NO_INVITE;
        }
        
        // If this invite has been marked answered then it is complete.
        if ($recipient->hasAnswered) {
            return self::COMPLETE_ANSWERS;
        }
        
        if ($recipient->answers()->count() > 0) {
            return self::PENDING_ANSWERS;
        } else {
            return self::NO_ANSWERS;
        }
    }
    
    /**
     * Creates an invite for a given user.
     *
     * @param   App\Models\Survey   $survey
     * @param   App\Models\User     $user
     * @param   App\Models\User     $invitedBy
     * @return  App\Models\SurveyRecipient
     */
    public static function make(Survey $survey, User $user, User $invitedBy = null)
    {
        $surveyRecipient = new self;
        $surveyRecipient->recipientId = $user->id;
        $surveyRecipient->link = str_random(32);
        $surveyRecipient->invitedById = $invitedBy ? $invitedBy->id : $user->id;
        $surveyRecipient->recipientType = 'users';
        $survey->recipients()->save($surveyRecipient);
        
        return $surveyRecipient;
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
        return $this->morphTo('recipient', 'recipientType', 'recipientId');
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
        return $this->survey->answers()->where([
            'answeredById'      => $this->recipientId,
            'answeredByType'    => $this->recipientType
        ]);
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
    
    /**
     * Returns the JSON representation of this model.
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        return [
            'key'       => $this->link,
            'final'     => $this->hasAnswered ? true : false,
            'answers'   => $this->answers
        ];
    }
}
