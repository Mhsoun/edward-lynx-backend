<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SurveyAnswer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\SurveyRecipient;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use App\Exceptions\CustomValidationException;

class AnswerController extends Controller
{
    
    /**
     * Show the form for creating a new resource.
     *
     * @param   Illuminate\Http\Request     $request
     * @return  Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'key'                   => [
                'required',
                Rule::exists('survey_recipients', 'link')->where(function ($query) {
                    $query->where('hasAnswered', 0);
                })
            ],
            'answers'               => 'required|array'
        ]);
        
        // Input items
        $key = $request->key;
        $answers = [];
        foreach ($request->answers as $answer) {
            $answers[$answer['question']] = $answer['answer'];
        }
        
        // Retrieve the recipient given the key and survey questions.
        $recipient = SurveyRecipient::where('link', $key)
            ->where('hasAnswered', 0)
            ->first();
        $questions = $recipient->survey->questions;
        
        // Validate answers.
        $errors = $this->validateAnswers($questions, $answers);
        if (!empty($errors)) {
            throw new CustomValidationException($errors);
        }
        
        // Save our answers.
        foreach ($questions as $q) {
            $question = $q->question;
            $answer = empty($answers[$question->id]) ? null : $answers[$question->id];
            
            // Skip if we don't have an answer.
            if ($answer === null) {
                continue;
            }
            
            // Create our answer.
            $surveyAnswer = new SurveyAnswer();
            $surveyAnswer->surveyId = $recipient->survey->id;
            $surveyAnswer->answeredById = $recipient->recipient->id;
            $surveyAnswer->questionId = $question->id;
            $surveyAnswer->invitedById = $recipient->invitedById;
            if ($question->answerTypeObject()->isNumeric()) {
                $surveyAnswer->answerValue = $answer;
            } else {
                $surveyAnswer->answerText = $answer;
            }
            
            $surveyAnswer->save();
        }
        
        // Mark the invite as answered.
        $recipient->hasAnswered = 1;
        $recipient->save();
        
        return response('', 201);
    }
    
    /**
     * Ensures that the submitted answers are valid for each question.
     *
     * @param   Illuminate\Database\Eloquent\Collection $questions
     * @param   array                                   $answers
     * @return  array
     */
    protected function validateAnswers(Collection $questions, array $answers)
    {
        $errors = [];
        
        foreach ($questions as $q) {
            $question = $q->question;
            $answer = $question->answerTypeObject();
            $key = "questions.{$question->id}";
            
            // Validate answers
            if (empty($answers[$question->id])) {
                // Make sure non-optional questions have an answer.
                if (!$question->optional) {
                    $errors[$key][] = "Missing answer for question with ID {$question->id}.";
                }
            } else {
                $ans = $answers[$question->id];
                
                // Questions that does not accept N/A should not receive one.
                if ($ans === -1 && !$question->isNA) {
                    $errors[$key][] = "N/A answer is not accepted.";
                // Ensure that the answer is a valid one.
                } elseif (!$answer->isValidValue($ans)) {
                    $errors[$key][] = "'{$ans}' is not a valid answer.";
                }
            }
        }
        
        return $errors;
    }
    
}
