<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstantFeedbackAnswer extends Model
{
    
    protected $fillable = ['answer'];
    
    public $timestamps = false;
    
    /**
     * Creates an answer to a instant feedback question.
     *
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @param   App\Models\User             $user
     * @param   App\Models\Question         $question
     * @param   array                       $answer
     * @return  App\Models\InstantFeedbackAnswer
     */
    public static function make(InstantFeedback $instantFeedback, User $user, Question $question, $answer)
    {
        if ($question->answerTypeObject()->isNumeric()) {
            $answer = intval($answer);
        }
        
        $ifAnswer = new self;
        $ifAnswer->instant_feedback_id = $instantFeedback->id;
        $ifAnswer->user_id = $user->id;
        $ifAnswer->question_id = $question->id;
        $ifAnswer->answer = $answer;
        $ifAnswer->save();
        
        return $ifAnswer;
    }
    
}
