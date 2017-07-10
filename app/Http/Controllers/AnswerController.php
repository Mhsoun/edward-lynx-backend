<?php namespace App\Http\Controllers;

use App\Models\User;
use App\Events\SurveyKeyExchanged;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\AnswerType;
use App\SurveyTypes;
use Log;

/**
* Displays the answers
*/
class AnswerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        Validator::extend('validAnswerValue', function($attribute, $value, $parameters)
        {
            return false;
        }, 'The value is not valid for an answer of the current type.');

        Validator::extend('validExtraValueDate', function($attribute, $value, $parameters)
        {
            return false;
        }, 'Please enter the date in the format YYYY-MM-DD.');
    }

    /**
    * Sets the locale based on the given survey
    */
    private function setSurveyLocale($survey)
    {
        app()->setLocale($survey->lang);
    }

    /**
     * Displays the answer page for the given survey
     * @param  int  $link An unique link for the recipient in the survey
     */
    public function show(Request $request, $link)
    {
        $surveyRecipient = \App\Models\SurveyRecipient::where('link', '=', $link)->first();

        //Check if exists
        if ($surveyRecipient == null) {
            return view('answer.notfound');
        }

        $survey = $surveyRecipient->survey;

        //Check if answered
        if ($surveyRecipient->hasAnswered == true) {
            //If they accessing the answer link, Progress candidates get redirected to the invite page
            if (\App\SurveyTypes::isNewProgress($survey) && $surveyRecipient->isCandidate()) {
                return redirect(action('InviteController@show', ['link' => $surveyRecipient->candidate()->link]));
            } else {
                return view('answer.alreadyanswered');
            }
        }

        $this->setSurveyLocale($survey);
        $surveyOwner = $survey->owner;

        //Check if started
        if (\Carbon\Carbon::now()->lt($survey->startDate)) {
            return view('answer.notstarted', compact('survey'));
        }

        // dd(\App\ExtraAnswerValue::valuesForSurvey($survey));

        $parserData = \App\EmailContentParser::createParserData($survey, $surveyRecipient);
        $categories = $survey->categoriesViewData($surveyRecipient);
        $autoAnswer = $request->autoAnswer ?: false;

        // Trigger a key exchange event if the email belongs to a registered user.
        if ($user = User::where('email', $surveyRecipient->mail)->first()) {
            event(new SurveyKeyExchanged($user, $surveyRecipient->link));
        }

        return view(
            "answer.view",
            compact(
                'link',
                'survey',
                'categories',
                'surveyOwner',
                'parserData',
                'surveyRecipient',
                'autoAnswer'));
    }

    /**
    * Validates the answers
    */
    private function validateAnswers($survey, $surveyRecipient, $request)
    {
        $answers = [];
        $answerValidations = [];

        foreach ($survey->questions as $question) {
            if (!$question->isTargetOf($surveyRecipient)) {
                continue;
            }

            $answerKey = 'answer_' . $question->questionId;
            $value = $request->input($answerKey);
            $validationRule = 'required';
            $answerType = $question->question->answerTypeObject();

            if ($question->question->optional) {
                $validationRule = '';
            }

            if ($answerType->isValidValue($value) || $question->question->optional) {
                if ($value != "") {
                    $answers[$question->questionId] = (object)[
                        'isText' => $answerType->isText(),
                        'value' => $value
                    ];
                }
            } else {
                $validationRule = $validationRule . "|validAnswerValue";
            }

            $answerValidations[$answerKey] = $validationRule;
        }

        $this->validate($request, $answerValidations);
        return $answers;
    }

    /**
    * Stores the answers
    */
    private function saveAnswers($survey, $answers, $surveyRecipient)
    {
        foreach ($answers as $questionId => $answerValue) {
            $answer = new \App\Models\SurveyAnswer;

            if ($answerValue->isText) {
                $answer->answerText = $answerValue->value;

                if ($answer->answerText == "") {
                    continue;
                }
            } else {
                $answer->answerValue = $answerValue->value;
            }

            $answer->questionId = $questionId;
            $answer->answeredById = $surveyRecipient->recipientId;
            $answer->invitedById = $surveyRecipient->invitedById;
            $survey->answers()->save($answer);
        }
    }

    /**
    * Valdidates the extra answers
    */
    private function validateExtraAnswers($survey, Request $request)
    {
        $extraAnswers = [];
        $validations = [];

        foreach (\App\ExtraAnswerValue::valuesForSurvey($survey) as $extraAnswer) {
            $answerKey = 'extraAnswer_' . $extraAnswer->id();
            $value = $request->input($answerKey);
            $validationRule = 'required';

            if ($extraAnswer->isOptional()) {
                $validationRule = '';
            }

            $isValid = true;

            if ($extraAnswer->isValidValue($value)) {
                $extraAnswers[$extraAnswer->id()] = (object)[
                    'type' => $extraAnswer->type(),
                    'value' => $value
                ];
            } else if ($extraAnswer->type() != \App\ExtraAnswerValue::Date) {
                $validationRule = $validationRule . "|validAnswerValue";
            } else if ($extraAnswer->type() == \App\ExtraAnswerValue::Date) {
                $validationRule = $validationRule . '|validExtraValueDate';
            }

            $validations[$answerKey] = $validationRule;
        }

        $this->validate($request, $validations);
        return $extraAnswers;
    }

    /**
    * Stores the answers
    */
    private function saveExtraAnswers($survey, $extraAnswers, $surveyRecipient)
    {
        foreach ($extraAnswers as $extraAnswerId => $answerValue) {
            $answer = new \App\Models\SurveyExtraAnswer;

            switch ($answerValue->type) {
                case \App\ExtraAnswerValue::Text:
                    $answer->textValue = $answerValue->value;
                    break;
                case \App\ExtraAnswerValue::Date:
                    $answer->dateValue = \Carbon\Carbon::parse($answerValue->value);
                    break;
                case \App\ExtraAnswerValue::Options:
                    $answer->numericValue = $answerValue->value;
                    break;
                case \App\ExtraAnswerValue::Hierarchy:
                    $answer->numericValue = $answerValue->value;
                    break;
            }

            $answer->extraQuestionId = $extraAnswerId;
            $answer->answeredById = $surveyRecipient->recipientId;
            $answer->invitedById = $surveyRecipient->invitedById;
            $survey->extraAnswers()->save($answer);
        }
    }

    /**
    * Saves the given top/worst list
    */
    private function saveTopWorstList($survey, $recipientId, $isTop, $list)
    {
        foreach ($list as $categoryId) {
            $topCategory = new \App\Models\SurveyTopWorstCategory;
            $topCategory->categoryId = $categoryId;
            $topCategory->isTop = $isTop;
            $topCategory->answeredById = $recipientId;
            $survey->topWorstCategories()->save($topCategory);
        }
    }

    /**
    * Stores an answer to storage
    */
    public function store(Request $request, $link)
    {
        $surveyRecipient = \App\Models\SurveyRecipient::where('link', '=', $link)->first();

        //Check if exists
        if ($surveyRecipient == null) {
            return view('answer.notfound');
        }

        $survey = $surveyRecipient->survey;
        $this->setSurveyLocale($survey);
        $surveyOwner = $survey->owner;

        //Check if answered
        if ($surveyRecipient->hasAnswered == true) {
            return view('answer.alreadyanswered', compact('survey', 'surveyOwner'));
        }

        //Check if started
        if (\Carbon\Carbon::now()->lt($survey->startDate)) {
            return view('answer.notstarted', compact('survey', 'surveyOwner'));
        }

        //Validate
        $answers = $this->validateAnswers($survey, $surveyRecipient, $request);

        if ($survey->type == \App\SurveyTypes::Normal) {
            $extraAnswers = $this->validateExtraAnswers($survey, $request);
        }

        //Save
        $this->saveAnswers($survey, $answers, $surveyRecipient);

        if ($survey->type == \App\SurveyTypes::Normal) {
            $this->saveExtraAnswers($survey, $extraAnswers, $surveyRecipient);
        }

        //Top/worst list
        if ($survey->type == \App\SurveyTypes::Normal) {
            if ($request->topList != null) {
                $this->saveTopWorstList($survey, $surveyRecipient->recipientId, true, $request->topList);
            }
        
            if ($request->worstList != null) {
                $this->saveTopWorstList($survey, $surveyRecipient->recipientId, false, $request->worstList);
            }
        }

        //Mark that the recipient has answered
        $surveyRecipient->hasAnswered = true;
        $surveyRecipient->save();

        //Try to create the user report
        if (\App\SurveyTypes::isNewProgress($survey))  {
            \App\Surveys::createUserReportLink(app(), $survey, $surveyRecipient->invitedByCandidate());
        }

        if (\App\SurveyTypes::isNewProgress($survey) && $surveyRecipient->isCandidate()) {
            return redirect(action('InviteController@show', ['link' => $surveyRecipient->candidate()->link]));
        } else {
            $parserData = \App\EmailContentParser::createParserData($survey, $surveyRecipient);
            return view('answer.success', compact('survey', 'surveyOwner', 'parserData'));
        }
    }

    /**
    * Generates a random string of the given length
    */
    function generateRandomString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
    * Generates random answers
    */
    public function generateRandomAnswers(Request $request, $id)
    {
        $survey = \App\Models\Survey::find($id);
        $count = $request->count ?: 0;
        $candidateId = null;

        if ($survey->type == SurveyTypes::Individual || $survey->type == SurveyTypes::Progress) {
            $roles = \App\Roles::get360();
            $candidateIndex = $request->candidateIndex ?: 0;
            $candidateId = $survey->candidates[$candidateIndex]->recipientId;
        } else if (SurveyTypes::isGroupLike($survey->type)) {
            $roles = \App\Roles::getLMTT()->toArray();
        }

        $numRoles = $roles->count();

        for ($i = 0; $i < $count; $i++) {
            //Create the recipients
            $recipient = new \App\Models\Recipient;
            $name = $this->generateRandomString(10);
            $recipient->name = $name;
            $recipient->mail = $name . '@randomdata.com';
            $recipient->ownerId = $survey->ownerId;
            $recipient->save();

            $surveyRecipient = new \App\Models\SurveyRecipient;
            $surveyRecipient->recipientId = $recipient->id;
            $surveyRecipient->invitedById = $candidateId;

            $surveyRecipient->link = str_random(32);
            $surveyRecipient->roleId = $roles[rand(0, $numRoles - 1)]->id;
            $survey->recipients()->save($surveyRecipient);

            $answers = [];

            //Generate the answers
            foreach ($survey->questions as $question) {
                $answerType = AnswerType::answerTypes()[$question->question->answerType];

                if (!$answerType->isText()) {
                    $answers[$question->questionId] = (object)[
                        'isText' => $answerType->isText(),
                        'value' => rand(-1, $answerType->maxValue())
                    ];
                } else {
                    $answers[$question->questionId] = (object)[
                        'isText' => $answerType->isText(),
                        'value' => $this->generateRandomString(10)
                    ];
                }
            }

            //Store the answers
            foreach ($answers as $questionId => $answerValue) {
                $answer = new \App\Models\SurveyAnswer;

                if ($answerValue->isText) {
                    $answer->answerText = $answerValue->value;
                } else {
                    $answer->answerValue = $answerValue->value;
                }

                $answer->questionId = $questionId;
                $answer->answeredById = $surveyRecipient->recipientId;
                $answer->invitedById = $surveyRecipient->invitedById;
                $survey->answers()->save($answer);
            }

            //Mark that the recipient has answered
            $surveyRecipient->hasAnswered = true;
            $surveyRecipient->save();
        }

        return "generated random answers";
    }
}
