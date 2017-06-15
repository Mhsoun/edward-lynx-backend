<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Question;
use App\Models\Recipient;
use Illuminate\Http\Request;
use App\Http\JsonHalResponse;
use App\Models\AnonymousUser;
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
use App\Jobs\ProcessInstantFeedbackInvites;
use App\Events\InstantFeedbackResultsShared;
use App\Exceptions\CustomValidationException;
use App\Exceptions\InvalidOperationException;
use App\Notifications\InstantFeedbackInvitation;
use App\Exceptions\InstantFeedbackClosedException;

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
                ->latest('createdAt')
                ->get();
        } elseif ($request->filter == 'to_answer') {
            $currentUser = $request->user();
            $result = InstantFeedback::answerableBy($currentUser)
                ->latest('createdAt')
                ->get();
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
        $this->notifyRecipients($instantFeedback, $request->recipients);

        $url = route('api1-instant-feedback', ['instantFeedback' => $instantFeedback]);
        return response(' ', 201, [
            'Location'      => $url,
            'Content-Type'  => 'application/json'
        ]);
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
        $currentUser = $request->user();

        if ($instantFeedback->user->id === $currentUser->id) {
            $key = null;
        } else {
            $recipient = $this->findRecipient($instantFeedback, $currentUser);
            $key = $instantFeedback->answerKeyOf($recipient);
        }
        
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
     * Adds additional recipients to an instant feedback.
     * 
     * @param   Request         $request
     * @param   InstantFeedback $instantFeedback
     * @return  Illuminate\Http\Response
     */
    public function recipients(Request $request, InstantFeedback $instantFeedback)
    {
        $this->validate($request, [
            'recipients.*.id'                   => 'required_without_all:recipients.*.name,recipients.*.email|integer|exists:users,id',
            'recipients.*.name'                 => 'required_without:recipients.*.id|string',
            'recipients.*.email'                => 'required_without:recipients.*.id|email'
        ]);

        // Make sure the instant feedback is still open.
        if ($instantFeedback->closed) {
            throw new InstantFeedbackClosedException;
        }

        $recipients = $request->recipients;
        $this->notifyRecipients($instantFeedback, $request->recipients);

        return response('', 201);
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
        // Prevent owners from answering their instant feedback.
        if ($instantFeedback->user->id == $request->user()->id) {
            //throw new InvalidOperationException('Answering your own instant feedback is not allowed.');
            // Allow for now.
        }

        // Do not accept answers on closed instant feedbacks.
        if ($instantFeedback->closed) {
            throw new InvalidOperationException('Instant feedback already closed for answers.');
        }

        $this->validate($request, [
            'key'                   => [
                'required',
                Rule::exists('instant_feedback_recipients', 'key')->where(function ($query) {
                    $query->where('answered', 0);
                })
            ],
            'answers'               => 'required|array|size:1',
            'answers.*.question'    => 'required|integer|exists:questions,id',
            'answers.*.value'       => 'required_without:answers.*.answer',
            'answers.*.answer'      => 'required_without:answers.*.value'
        ]);
            
        $currentUser = $request->user();
        $recipient = $this->findRecipient($instantFeedback, $currentUser);
        $key = $request->key;
        $question = Question::find(intval($request->answers[0]['question']));
        
        if (isset($request->answers[0]['value'])) {
            $answer = $request->answers[0]['value'];
        } else {
            $answer = $request->answers[0]['answer'];
        }
        
        try {
            InstantFeedbackAnswer::make($instantFeedback, $recipient, $question, $answer);
        } catch (InvalidArgumentException $e) {
            throw new CustomValidationException([
                'answers.0.answer'  => $e->getMessage()
            ]);
        }
        
        $ifRecipient = InstantFeedbackRecipient::where('key', $key)->first();
        $ifRecipient->markAnswered();
        $ifRecipient->save();
        
        return createdResponse();
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
     * Exchanges an instant feedback answer key to a instant feedback id.
     * 
     * @param  Illuminate\Http\Request  $request
     * @param  string                   $key
     * @return App\Http\JsonHalResponse
     */
    public function exchange(Request $request, $key)
    {
        $currentUser = $request->user();
        $recipients = Recipient::where('mail', $currentUser->email)
                        ->get()
                        ->map(function($recipient) {
                            return $recipient->id;
                        });
        $ifRecipient = InstantFeedbackRecipient::where('key', $key)
                        ->whereIn('recipientId', $recipients)
                        ->firstOrFail();

        return response()->jsonHal([
            'instant_feedback_id' => $ifRecipient->instantFeedbackId
        ]);
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

    /**
     * Sends notifications to instant feedback recipients.
     * 
     * @param  App\Models\InstantFeedback   $instantFeedback
     * @param  array                        $recipients
     * @return array
     */
    protected function notifyRecipients(InstantFeedback $instantFeedback, array $recipients)
    {
        $currentUser = request()->user();
        $results = [];

        foreach ($recipients as $r) {
            $user = null;

            if (!empty($r['id'])) {
                $user = User::find($r['id']);
                if (!$currentUser->can('view', $user)) {
                    throw new InvalidArgumentException("Current user cannot access user with ID $user->id.");
                }

                $name = $user->name;
                $email = $user->email;
            } else {
                $name = $r['name'];
                $email = $r['email'];
            }

            $recipient = Recipient::make($currentUser->id, $name, $email, '');
            $ifRecipient = InstantFeedbackRecipient::make($instantFeedback, $recipient);

            if (!$ifRecipient->notified) {
                if (isset($user)) {
                    $user->notify(new InstantFeedbackInvitation($instantFeedback->id, $instantFeedback->user->name, $ifRecipient->key));
                } else {
                    $recipient->notify(new InstantFeedbackInvitation($instantFeedback->id, $instantFeedback->user->name, $ifRecipient->key));
                }
                $ifRecipient->notified = true;
                $ifRecipient->save();
            }

            $results[] = $recipient;
        }

        return $results;
    }

    /**
     * Finds the recipient record for the provided user.
     * 
     * @param  App\Models\InstantFeedback   $instantFeedback
     * @param  App\Models\User              $user
     * @return App\Models\Recipient
     */
    protected function findRecipient(InstantFeedback $instantFeedback, User $user)
    {
        return Recipient::where([
            'ownerId'   => $instantFeedback->user->id,
            'mail'      => $user->email
        ])->first();
    }
    
}
