<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Represents a recipient in a survey
*/
class SurveyRecipient extends Model
{
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
        return $this->belongsTo('\App\Models\Recipient', 'recipientId');
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
