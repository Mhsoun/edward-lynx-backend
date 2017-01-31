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
use App\Exceptions\SurveyMissingAnswersException;

class AnswerController extends Controller
{
    
    /**
     * Returns the user's answers to a survey.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  App\Http\JsonHalResponse
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
        
        // If this is final, make sure all questions have answers.
        $this->validateAnswerCompleteness($survey, $recipient->answers, $answers);
        $errors = $this->validateAnswerCompleteness($survey, $recipient->answers, $answers);
        if (!empty($errors)) {
            throw new CustomValidationException($errors);
        }
        
        // Save our answers.
        foreach ($questions as $q) {
            $question = $q->question;
            $answer = isset($answers[$question->id]) ? $answers[$question->id] : null;
            
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
        
        $recipient = SurveyRecipient::where([
            'surveyId'      => $recipient->survey->id,
            'recipientId'   => $recipient->recipientId,
            'invitedById'   => $recipient->invitedById,
            'recipientType' => $recipient->recipientType
        ])->first();
        
        return response()->jsonHal($recipient);
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
        
        foreach ($answers as $questionId => $answer) {
            $question = $questions->where('questionId', $questionId)
                                  ->first()
                                  ->question;
            $answerType = $question->answerTypeObject();
            $key = "questions.{$question->id}";
            
            if ($answer == -1 && !$question->isNA) {
                $errors[$key][] = "N/A answer is not accepted.";
            } elseif (!$answerType->isValidValue($answer)) {
                $errors[$key][] = "'{$answer}' is not a valid answer.";
            }
        }
        
        return $errors;
    }
    
    /**
     * Ensures that the user has submitted complete answers to the survey.
     *
     * @param   App\Models\Survey                       $survey
     * @param   Illuminate\Database\Eloquent\Collection $answers
     * @param   array                                   $newAnswers
     */
    protected function validateAnswerCompleteness(Survey $survey, Collection $answers, array $newAnswers)
    {
        
        $answerVals = [];
        foreach ($answers as $answer) {
            if (!isset($answers[$answer->questionId])) {
                $answerVals[$answer->questionId] = $answer->value;
            }
        }
        
        foreach ($newAnswers as $questionId => $answer) {
            $answerVals[$questionId] = $answer;
        }
        
        $errors = [];
        foreach ($survey->questions as $question) {
            $questionId = $question->questionId;
            if (!isset($answerVals[$questionId]) && !$question->optional) {
                $errors[] = [
                    'question'  => $questionId,
                    'message'   => "Question with ID {$questionId} is missing an answer."     ];
            }
        }
        
        if (!empty($errors)) {
            throw new SurveyMissingAnswersException($errors);
        }
    }
    
}
