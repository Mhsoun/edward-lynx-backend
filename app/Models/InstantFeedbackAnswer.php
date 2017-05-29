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
        $results = [];
        
        // Process custom input questions
        if ($question->answerType == 5) {
            $results['frequencies'] = [];
            foreach ($answers as $answer) {
                $results['frequencies'][] = [
                    'value' =>  $answer->answer,
                    'count' =>  1
                ];
            }
    
        // Questions with fixed values
        } else {
            $answerObj = $question->answerTypeObject();
            $possibleValues = $answerObj->valuesFlat();
            $counts = [];
   
            // If the question allows a N/A option, add a -1 value
            if ($question->isNA) {
               $counts['-1'] = 0;
            }

            // Initialize possible values to zero
            foreach ($possibleValues as $val) {
               $key = strval($val);
               $counts[$key] = 0;
            }

            // Calculate frequencies of each question value
            foreach ($answers as $answer) {
               $key = strval($answer->answer);
               if (isset($counts[$key])) {
                   $counts[$key] += 1;
               }
            }

            // Build a proper result array
            foreach ($counts as $key => $count) {
               $results['frequencies'][] = [
                   'value'          => $key,
                   'description'    => strval($answerObj->descriptionOfValue($key)),
                   'count'          => $count
               ];
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
        return $this->belongsTo(InstantFeedback::class, 'instantFeedbackId');
    }
    
    /**
     * Returns the user who sent this answer.
     *
     * @return   Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Recipient::class, 'recipientId');
    }
    
    /**
     * Returns the question this answer belongs to.
     *
     * @return   Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function question()
    {
        return $this->belongsTo(Question::class, 'questionId');
    }
    
    /**
     * Creates an answer to a instant feedback question.
     *
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @param   App\Models\Recipient        $recipient
     * @param   App\Models\Question         $question
     * @param   array                       $answer
     * @return  App\Models\InstantFeedbackAnswer
     */
    public static function make(InstantFeedback $instantFeedback, Recipient $recipient, Question $question, $answer)
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
        $ifAnswer->instantFeedbackId = $instantFeedback->id;
        $ifAnswer->recipientId = $recipient->id;
        $ifAnswer->questionId = $question->id;
        $ifAnswer->answer = $answer;
        $ifAnswer->save();
        
        return $ifAnswer;
    }
    
}
