<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Question;
use App\Models\Recipient;
use Illuminate\Http\Request;
use App\Http\JsonHalResponse;
use InvalidArgumentException;
use App\Models\InstantFeedback;
use Illuminate\Validation\Rule;
use App\Models\QuestionCategory;
use Illuminate\Support\Collection;
use App\Models\QuestionCustomValue;
use App\Http\Controllers\Controller;
use App\Models\InstantFeedbackShare;
use App\Models\InstantFeedbackAnswer;
use App\Models\InstantFeedbackQuestion;
use App\Models\InstantFeedbackRecipient;
use App\Events\InstantFeedbackResultsShared;
use App\Exceptions\CustomValidationException;
use App\Notifications\InstantFeedbackRequested;

class InstantFeedbackController extends Controller
{
    
    /**
     * Returns a list of instant feedbacks.
     *
     * @return  App\Http\JsonHalResponse
     */
    public function index(Request $request)
    {
        $this->validate($request, [
            'filter'    => 'required|string|in:mine,to_answer'
        ]);
        
        $result = [];
        if ($request->filter == 'mine') {
            $result = InstantFeedback::mine()
                ->oldest()
                ->get();
        } elseif ($request->filter == 'to_answer') {
            $currentUser = $request->user();
            $result = InstantFeedback::answerableBy($currentUser)
                ->oldest('createdAt')
                ->get();
            /*
            $result = [];
            foreach ($instantFeedbacks as $if) {
                $result[] = array_merge([
                        '_links'    => JsonHalResponse::generateModelLinks($if)
                    ],
                    $if->jsonSerialize(),
                    [ 'key'   => $if->answerKeyOf($currentUser) ]
                );
            }
            */
        }
        
        return response()->jsonHal($result);
    }
    
    /**
     * Creates a instant feedback.
     *
     * @param   Illuminate\Http\Request     $request
     * @param   Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'lang'                              => 'required|in:en,fi,sv',
            'anonymous'                         => 'boolean',
            'questions'                         => 'required|array|size:1',
            'questions.*.text'                  => 'required|string',
            'questions.*.isNA'                  => 'required|boolean',
            'questions.*.answer.type'           => 'required|in:0,1,2,3,4,5,6,7,8',
            'questions.*.answer.options'        => 'array',
            'recipients'                        => 'required|array',
            'recipients.*.id'                   => 'required_without_all:recipients.*.name,recipients.*.email|integer|exists:users,id',
            'recipients.*.name'                 => 'required_without:recipients.*.id|string',
            'recipients.*.email'                => 'required_without:recipients.*.id|email'
        ]);
            
        $instantFeedback = new InstantFeedback($request->all());
        $instantFeedback->userId = $request->user()->id;
        $instantFeedback->save();
        
        $this->processQuestions($request->user(), $instantFeedback, $request->questions);
        $recipients = $this->processRecipients($instantFeedback, $request->recipients);
        
        // Notify each recipient of the instant feedback.
        foreach ($recipients as $recipient) {
            if (!$recipient instanceof User) {
                continue; // TODO: skip notifying non-users.
            }

            $recipient->user->notify(new InstantFeedbackRequested($instantFeedback));
        }
        
        $url = route('api1-instant-feedback', ['instantFeedback' => $instantFeedback]);
        return response('', 201, ['Location' => $url]);
    }
    
    /**
     * Shows instant feedback details.
     *
     * @param   Illuminate\Http\Request     $request
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  App\Http\JsonHalResponse
     */
    public function show(Request $request, InstantFeedback $instantFeedback)
    {
        dd($instantFeedback->recipients);
        $currentUser = $request->user();
        $key = $instantFeedback->answerKeyOf($currentUser);
        
        return response()->jsonHal($instantFeedback)
                         ->with([
                             'key' => $key
                         ]);
    }
    
    /**
     * Updates an instant feedback.
     *
     * @param   Illuminate\Http\Request     $request
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  App\Http\JsonHalResponse
     */
    public function update(Request $request, InstantFeedback $instantFeedback)
    {   
        if ($request->closed) {
            $instantFeedback->close()
                            ->save();
        } else {
            $instantFeedback->open()
                            ->save();
        }
        
        return response()->jsonHal($instantFeedback);
    }
    
    /**
     * Accepts answers to an instant feedback.
     *
     * @param   Illuminate\Http\Request     $request
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  Illuminate\Http\Response
     */
    public function answer(Request $request, InstantFeedback $instantFeedback)
    {
        $this->validate($request, [
            'key'                   => [
                'required',
                Rule::exists('instant_feedback_recipients', 'key')->where(function ($query) {
                    $query->where('answered', 0);
                })
            ],
            'answers'               => 'required|array|size:1',
            'answers.*.question'    => 'required|integer|exists:questions,id',
            'answers.*.answer'      => 'required'
        ]);
            
        $currentUser = $request->user();
        $key = $request->key;
        $question = Question::find(intval($request->answers[0]['question']));
        $answer = $request->answers[0]['answer'];
        
        try {
            InstantFeedbackAnswer::make($instantFeedback, $currentUser, $question, $answer);
        } catch (InvalidArgumentException $e) {
            throw new CustomValidationException([
                'answers.0.answer'  => $e->getMessage()
            ]);
        }
        
        $recipient = InstantFeedbackRecipient::where([
            'userId'    => $currentUser->id,
            'key'       => $key
        ])->first();
        $recipient->markAnswered();
        $recipient->save();
        
        return response('', 201);
    }
    
    /**
     * Returns answer frequencies and statistics.
     *
     * @param   Illuminate\Http\Request     $request
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  App\Http\JsonHalResponse
     */
    public function answers(Request $request, InstantFeedback $instantFeedback)
    {
        $results = $instantFeedback->calculateAnswers();
        return response()->jsonHal($results);
    }
    
    /**
     * Shares instant answer results to other users.
     *
     * @param   Illuminate\Http\Request     $request
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  Illuminate\Http\Response
     */
    public function share(Request $request, InstantFeedback $instantFeedback)
    {
        $this->validate($request, [
            'users'     => 'required|array',
            'users.*'   => 'integer|exists:users,id'
        ]);
        
        $users = $request->users;
        $currentUser = $request->user();
        
        // Make sure the users are colleagues of the current user.
        $errors = [];
        $colleagues = $currentUser->colleagues()->map(function($user) {
            return $user->id;
        })->toArray();
        foreach ($users as $index => $user) {
            if (!in_array($user, $colleagues)) {
                $errors["users.{$index}"] = "User {$user} is not in the same company as the current user.";
            }
        }
        
        // If we have errors, throw it early.
        if (!empty($errors)) {
            throw new CustomValidationException($errors);
        }
        
        // Create the records.
        $userObjects = new Collection;
        foreach ($users as $userId) {
            $user = User::find($userId);
            InstantFeedbackShare::make($instantFeedback, $user);
            $userObjects->push($user);
        }
        
        event(new InstantFeedbackResultsShared($instantFeedback, $userObjects));
        
        return response('', 201);
    }
    
    /**
     * Processes submitted questions and creates the appropriate
     * records in the database for each.
     *
     * @param   App\Models\User             $user
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @param   array                       $questions
     * @return  void
     */
    protected function processQuestions(User $user, InstantFeedback $instantFeedback, array $questions)
    {
        $category = QuestionCategory::findCategoryForInstantFeedbacks($user, $instantFeedback->lang);
        foreach ($questions as $q) {
            $question = new Question;
            $question->text = $q['text'];
            $question->ownerId = $user->id;
            $question->categoryId = $category->id;
            $question->answerType = $q['answer']['type'];
            $question->isNA = $q['isNA'];
            $question->save();
            
            if ($q['answer']['type'] == 8) {
                foreach ($q['answer']['options'] as $o) {
                    $value = new QuestionCustomValue;
                    $value->name = $o;
                    $value->questionId = $question->id;
                    $value->save();
                }
            }
            
            $questionLink = new InstantFeedbackQuestion();
            $questionLink->instantFeedbackId = $instantFeedback->id;
            $questionLink->questionId = $question->id;
            $questionLink->save();
        }
    }
    
    /**
     * Processes each recipient and creates recipient records for each.
     *
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @param   array                       $recipients
     * @return  array
     */
    protected function processRecipients(InstantFeedback $instantFeedback, array $recipients)
    {
        $currentUser = request()->user();
        $results = [];
        
        foreach ($recipients as $r) {
            if (isset($r['id'])) {
                $user = User::find($r['id']);
                if (!$currentUser->can('view', $user)) {
                    throw new InvalidArgumentException("Current user cannot access user with ID $user->id.");
                }
            } else {
                $user = Recipient::make($currentUser->id, $r['name'], $r['email'], '');
            }
            $results[] = InstantFeedbackRecipient::make($instantFeedback, $user);
        }
        
        return $results;
    }
    
}
