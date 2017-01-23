<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Survey;
use App\Models\SurveyAnswer;
use Illuminate\Http\Request;
use App\Models\SurveyRecipient;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use App\Exceptions\CustomValidationException;
use App\Exceptions\SurveyAnswersFinalException;

class AnswerController extends Controller
{
    
    /**
     * Returns the user's answers to a survey.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  App\Http\HalResponse
     */
    public function index(Request $request, Survey $survey)
    {
        $user = $request->user();
        $recipient = SurveyRecipient::where([
            'surveyId'      => $survey->id,
            'recipientId'   => $user->id,
            'recipientType' => 'users'
        ])->first();
        
        // If we can't find an invite then the user is an admin.
        // Create an invite for him/her.
        if (!$recipient && $user->can('administer', $survey)) {
            $recipient = SurveyRecipient::make($survey, $user);
        }
        
        return response()->jsonHal($recipient);
    }
    
    /**
     * Answers a survey.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  Illuminate\Http\Response
     */
    public function answer(Request $request, Survey $survey)
    {   
        $this->validate($request, [
            'key'                   => 'required|string',
            'answers'               => 'required|array',
            'final'                 => 'boolean'
        ]);
            
        // Input items
        $key = $request->key;
        $answers = [];
        foreach ($request->answers as $answer) {
            $answers[$answer['question']] = $answer['answer'];
        }
        $final = $request->input('final', true);
        $user = $request->user();
        $questions = $survey->questions;
        
        if (empty($key)) {
            if (!$user->can('administer', $survey)) {
                throw new CustomValidationException([
                    'key'   => ['Missing answer key.']
                ]);
            }
            
            $recipient = SurveyRecipient::make($survey, $user);
            $key = $recipient->link;
        } else {
            $this->validate($request, ['key' => 'exists:survey_recipients,link']);
            $recipient = SurveyRecipient::where([
                'surveyId'      => $survey->id,
                'link'          => $key,
                'recipientId'   => $user->id,
                'recipientType' => 'users'
            ])->first();
                
            if (!$recipient) {
                throw new CustomValidationException([
                    'key'   => ['Invalid answer key.']
                ]);
            }
            
            $key = $recipient->link;
        }
        
        // Make sure this survey hasn't expired yet.
        if ($survey->endDate->isPast()) {
            throw new SurveyExpiredException();
        }
        
        // Make sure the answers aren't finalized yet.
        if ($recipient->hasAnswered) {
            throw new SurveyAnswersFinalException();
        }
        
        // Validate answers.
        $errors = $this->validateAnswers($questions, $answers, $final);
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
            
            // Try to check if we have an answer to the question
            $surveyAnswer = SurveyAnswer::where([
                'answeredById'      => $user->id,
                'answeredByType'    => 'users',
                'questionId'        => $question->id,
                'invitedById'       => $recipient->invitedById
            ])->first();
            
            // Create our answer if there is none.
            if (!$surveyAnswer) {
                $surveyAnswer = new SurveyAnswer();
                $surveyAnswer->answeredById = $user->id;
                $surveyAnswer->answeredByType = 'users';
                $surveyAnswer->questionId = $question->id;
                $surveyAnswer->invitedById = $recipient->invitedById;
            }
            
            // For some reason, agreement scales are treated as text
            $numerics = [0, 1, 2, 3, 4, 6, 7];
            if (in_array($question->answerType, $numerics)) {
                $surveyAnswer->answerValue = $answer;
            } else {
                $surveyAnswer->answerText = $answer;
            }

            $survey->answers()->save($surveyAnswer);
        }
        
        // Mark the invite as answered.
        if ($final) {
            $recipient->hasAnswered = 1;
            $recipient->save();
        }
        
        return response()->jsonHal($recipient);
    }
    
    /**
     * Ensures that the submitted answers are valid for each question.
     *
     * @param   Illuminate\Database\Eloquent\Collection $questions
     * @param   array                                   $answers
     * @param   boolean                                 $complete
     * @return  array
     */
    protected function validateAnswers(Collection $questions, array $answers, $complete = true)
    {
        $errors = [];
        
        foreach ($questions as $q) {
            $question = $q->question;
            $answer = $question->answerTypeObject();
            $key = "questions.{$question->id}";
            
            // Validate answers
            if (empty($answers[$question->id])) {
                // Make sure non-optional questions have an answer.
                if ($complete && !$question->optional) {
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
