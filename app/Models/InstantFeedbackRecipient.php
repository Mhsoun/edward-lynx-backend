<?php

namespace App\Models;

use Carbon\Carbon;
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
     * Attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['answered_at'];
    
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
    
    /**
     * Marks this recipient as answered.
     * NOTE: This doesn't save this model!
     *
     * @return  void
     */
    public function markAnswered()
    {
        $this->answered = 1;
        $this->answered_at = Carbon::now();
    }
    
}
