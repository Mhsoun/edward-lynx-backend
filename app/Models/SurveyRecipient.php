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
     * @param   App\Models\User|string  $user
     * @param   string|null             $email
     * @return  int
     */
    public static function surveyStatus(Survey $survey, User $user, $email = null)
    {
        if ($user instanceof User) {
            $recipient = Recipient::where([
                'ownerId' => $survey->ownerId,
                'user_id' => $user->id
            ])->first();
            
            if (!$recipient) {
                throw new InvalidArgumentException("Cannot find a recipient for the given user.");
            }
            
        } elseif (is_string($user) && !is_null($email)) {
            $recipient = Recipient::where([
                'ownerId'   => $survey->ownerId,
                'name'      => $user,
                'mail'      => $email
            ])->first();
        }
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
}
