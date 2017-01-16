<?php

namespace App\Models;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class InstantFeedbackAnswer extends Model
{
    
    protected $fillable = ['answer'];
    
    public $timestamps = false;
    
    /**
     * Calculates frequencies and statistics of a answer set.
     *
     * @param   App\Models\Question                     $question
     * @param   Illuminate\Database\Eloquent\Collection $answers
     * @return  array
     */
    public static function calculate(Question $question, Collection $answers)
    {
       $possibleValues = $question->answerTypeObject()->valuesFlat();
       $results = [];
       
       // If the question allows a N/A option, add a -1 value
       if ($question->isNA) {
           $results['frequencies']['-1'] = 0;
       }
       
       // Initialize possible values to zero
       foreach ($possibleValues as $val) {
           $key = strval($val);
           $results['frequencies'][$key] = 0;
       }
       
       // Calculate frequencies of each question value
       foreach ($answers as $answer) {
           $key = strval($answer->answer);
           if (isset($results['frequencies'][$key])) {
               $results['frequencies'][$key] += 1;
           }
       }
       
       $results['totalAnswers'] = count($answers);
       
       return $results;
    }
    
    /**
     * Returns the instant feedback this answer belongs to.
     *
     * @return   Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instantFeedback()
    {
        return $this->belongsTo('App\Models\InstantFeedback');
    }
    
    /**
     * Returns the user who sent this answer.
     *
     * @return   Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
    
    /**
     * Returns the question this answer belongs to.
     *
     * @return   Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function question()
    {
        return $this->belongsTo('App\Models\Question');
    }
    
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
        $answerType = $question->answerTypeObject();
        if (!$question->isNA && $answer == -1) {
            throw new InvalidArgumentException('Question does not accept N/A answers.');
        }
        
        if (!$answerType->isValidValue($answer)) {
            throw new InvalidArgumentException('Invalid answer.');
        }
        
        if ($answerType->isNumeric()) {
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
