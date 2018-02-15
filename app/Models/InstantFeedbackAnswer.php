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
        $nonAnonCount = 0;
        $results = [];
        
        // Process custom input questions
        if ($question->answerType == 5) {
            $results['frequencies'] = [];
            foreach ($answers as $answer) {
                if ($answer->anonymous) {
                    $submitter = [];
                } else {
                    $submitter = [[
                        'id' => $answer->user->id,
                        'name' => $answer->user->name,
                        'email' => $answer->user->email,
                    ]];
                }

                $results['frequencies'][] = [
                    'value'         =>  $answer->answer,
                    'count'         =>  1,
                    'submissions'   => $submitter,
                ];
            }
    
        // Questions with fixed values
        } else {
            $answerObj = $question->answerTypeObject();
            $possibleValues = $answerObj->valuesFlat();
            $counts = [];
            $submissions = [];
   
            // If the question allows a N/A option, add a -1 value
            if ($question->isNA) {
               $counts['-1'] = 0;
               $submissions['-1'] = [];
            }

            // Initialize possible values to zero
            foreach ($possibleValues as $val) {
               $key = strval($val);
               $counts[$key] = 0;
               $submissions[$key] = [];
            }

            // Calculate frequencies of each question value
            foreach ($answers as $answer) {
               $key = strval($answer->answer);
               if (isset($counts[$key])) {
                   $counts[$key] += 1;
               }

               // Record users who chose not to be anonymous
               if ($answer->anonymous != 1) {
                    $submissions[$key][] = [
                        'id' => $answer->user->id,
                        'name' => $answer->user->name,
                        'email' => $answer->user->email,
                    ];
                    $nonAnonCount++;
               }
            }

            // Build a proper result array
            foreach ($counts as $key => $count) {
               $results['frequencies'][] = [
                   'value'          => $key,
                   'description'    => strval($answerObj->descriptionOfValue($key)),
                   'count'          => $count,
                   'submissions'    => $submissions[$key],
               ];
            }
        }
       
        $results['totalAnswers'] = count($answers);
        $results['totalAnonymousAnswers'] = $results['totalAnswers'] - $nonAnonCount;
       
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
     * @param   array                       $data
     * @return  App\Models\InstantFeedbackAnswer
     */
    public static function make(InstantFeedback $instantFeedback, Recipient $recipient, array $data)
    {
        $answerType = $data['question']->answerTypeObject();
        if (!$data['question']->isNA && $data['answer'] == -1) {
            throw new InvalidArgumentException('Question does not accept N/A answers.');
        }
        
        if (!$answerType->isValidValue($data['answer'])) {
            throw new InvalidArgumentException('Invalid answer.');
        }
        
        if ($answerType->isNumeric()) {
            $data['answer'] = intval($data['answer']);
        }
        
        $ifAnswer = new self;
        $ifAnswer->instantFeedbackId = $instantFeedback->id;
        $ifAnswer->recipientId = $recipient->id;
        $ifAnswer->questionId = $data['question']->id;
        $ifAnswer->answer = $data['answer'];
        $ifAnswer->anonymous = $data['anonymous'];
        $ifAnswer->save();
        
        return $ifAnswer;
    }
    
}
