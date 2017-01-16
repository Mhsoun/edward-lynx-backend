<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstantFeedbackRecipient extends Model
{
    
    /**
     * Disable timestamps for this model.
     *
     * @var boolean
     */
    public $timestamps = false;
    
    protected $fillable = [];
    
    /**
     * Creates a new recipient record for a user.
     *
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @param   App\Models\User             $user
     * @return  App\Models\InstantFeedbackRecipient
     */
    public static function make(InstantFeedback $instantFeedback, User $user)
    {
        $key = str_random(32);
        $recipient = new self;
        $recipient->instant_feedback_id = $instantFeedback->id;
        $recipient->user_id = $user->id;
        $recipient->key = $key;
        $recipient->save();
        return $recipient;
    }
    
    /**
     * Returns the recipient user.
     *
     * @return  App\Models\User
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    
    /**
     * Returns the instant feedback.
     *
     * @param   App\Models\InstantFeedback
     */
    public function instantFeedback()
    {
        return $this->belongsTo('App\Models\InstantFeedback');
    }
    
}
