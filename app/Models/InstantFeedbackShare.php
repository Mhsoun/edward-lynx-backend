<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstantFeedbackShare extends Model
{
    
    /**
     * Creates a share record for a user to an instant feedback.
     *
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @param   App\Models\User             $user
     * @return  App\Models\InstantFeedbackShare
     */
    public static function make(InstantFeedback $instantFeedback, User $user)
    {
        $share = self::where([
            'instant_feedback_id'   => $instantFeedback->id,
            'user_id'               => $user->id
        ])->first();
        
        if (!$share) {
            $share = new self;
            $share->instant_feedback_id = $instantFeedback->id;
            $share->user_id = $user->id;
            $share->save();
        }
        
        return $share;
    }
    
    /**
     * Returns TRUE if the provided instant feedback has been shared to the
     * provided user.
     *
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @param   App\Models\User             $user
     * @return  boolean
     */
    public static function isShared(InstantFeedback $instantFeedback, User $user)
    {
        $share = self::where([
            'instant_feedback_id'   => $instantFeedback->id,
            'user_id'               => $user->id
        ])->first();
            
        return !is_null($share);
    }
    
}
