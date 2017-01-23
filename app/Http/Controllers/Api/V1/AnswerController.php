<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Survey;
use Illuminate\Http\Request;
use App\Models\SurveyRecipient;
use App\Http\Controllers\Controller;
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
        
        if (!$user->can('administer', $survey)) {
            $this->validate(['key'   => 'exists:survey_recipients,link']);
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
        } elseif ($user->can('administer', $survey) && empty($key)) {
            $recipient = SurveyRecipient::make($survey, $user);
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
            $surveyAnswer->surveyId = $survey->id;
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
        if ($final) {
            $recipient->hasAnswered = 1;
            $recipient->save();
        }
        
        return response()->jsonHal($recipient);
    }
    
}
