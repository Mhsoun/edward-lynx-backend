<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Survey;
use App\Models\Recipient;
use App\Models\SurveyAnswer;
use Illuminate\Http\Request;
use App\Models\SurveyRecipient;
use App\Http\Controllers\Controller;
use App\Exceptions\SurveyExpiredException;
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
        $currentUser = $request->user();
        $recipient = Recipient::where([
            'ownerId'   => $survey->ownerId,
            'mail'      => $currentUser->email
        ])->first();
        $surveyRecipient = SurveyRecipient::where([
            'surveyId'      => $survey->id,
            'recipientId'   => $recipient->id
        ])->first();
        
        return response()->jsonHal($surveyRecipient);
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
            'key'                   => 'required|exists:survey_recipients,link',
            'answers'               => 'required|array',
            'answers.*.question'    => 'required|integer|exists:questions,id',
            'answers.*.value'       => 'required',
            'final'                 => 'boolean'
        ]);
            
        // Input items
        $key = $request->key;
        $answers = [];
        foreach ($request->answers as $answer) {
            $answers[$answer['question']] = $answer['value'];
        }
        $final = $request->input('final', true);
        $recipient = SurveyRecipient::where([
            'surveyId'  => $survey->id,
            'link'      => $key
        ])->first();
        $user = $request->user();
        $questions = $survey->questions;
        
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
        if ($final) {
            $this->validateAnswerCompleteness($survey, $recipient->answers, $answers);
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
                'answeredById'      => $recipient->id,
                'questionId'        => $question->id,
                'invitedById'       => $recipient->invitedById
            ])->first();
            
            // Create our answer if there is none.
            if (!$surveyAnswer) {
                $surveyAnswer = new SurveyAnswer();
                $surveyAnswer->answeredById = $user->id;
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
     * Returns survey results.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\Survey       $survey
     * @return  App\Http\JsonHalResponse
     */
    public function results(Request $request, Survey $survey)
    {
        $results = $survey->calculateAnswers();
        
        // Rearrange frequency results
        $freqs = [];
        foreach ($results['frequencies'] as $questionId => $counts) {
            $freqs[] = compact('questionId', 'counts');
        }
        $results['frequencies'] = $freqs;
            
        return response()->jsonHal($results)
                         ->withLinks([
                             'survey'   => $survey->url()
                         ]);
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
