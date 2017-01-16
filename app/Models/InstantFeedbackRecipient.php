<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstantFeedbackRecipient extends Model
{
    
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
        return $instantFeedback->recipients()->create([
            'user_id'   => $user->id,
            'key'       => $key
        ]);
        return $ifRecipient;
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
