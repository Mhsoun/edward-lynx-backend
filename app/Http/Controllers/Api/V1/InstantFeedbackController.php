<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Question;
use App\Http\HalResponse;
use Illuminate\Http\Request;
use App\Models\InstantFeedback;
use Illuminate\Validation\Rule;
use App\Models\QuestionCategory;
use App\Models\QuestionCustomValue;
use App\Http\Controllers\Controller;
use App\Models\InstantFeedbackAnswer;
use App\Models\InstantFeedbackQuestion;
use App\Models\InstantFeedbackRecipient;

class InstantFeedbackController extends Controller
{
    
    /**
     * Returns a list of instant feedbacks.
     *
     * @return  App\Http\HalResponse
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
            $instantFeedbacks = InstantFeedback::answerableBy($currentUser)
                ->oldest()
                ->get();
            
            $result = [];
            foreach ($instantFeedbacks as $if) {
                $result[] = array_merge([
                        '_links'    => HalResponse::generateModelLinks($if)
                    ],
                    $if->jsonSerialize(),
                    [ 'key'   => $if->answerKeyOf($currentUser) ]
                );
            }
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
            'questions.*.answer.*.description'  => 'string',
            'questions.*.answer.*.value'        => 'string',
            'recipients'                        => 'required|array',
            'recipients.*.id'                   => 'required|integer|exists:users'
        ]);
            
        $instantFeedback = new InstantFeedback($request->all());
        $instantFeedback->user_id = $request->user()->id;
        $instantFeedback->save();
        
        $this->processQuestions($request->user(), $instantFeedback, $request->questions);
        $this->processRecipients($instantFeedback, $request->recipients);
        
        $url = route('api1-instant-feedback', ['instantFeedback' => $instantFeedback]);
        return response('', 201, ['Location' => $url]);
    }
    
    /**
     * Shows instant feedback details.
     *
     * @param   Illuminate\Http\Request     $request
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @return  App\Http\HalResponse
     */
    public function show(Request $request, InstantFeedback $instantFeedback)
    {
        return response()->jsonHal($instantFeedback);
    }
    
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
        
        InstantFeedbackAnswer::make($instantFeedback, $currentUser, $question, $answer);
        
        $recipient = InstantFeedbackRecipient::where([
            'user_id'   => $currentUser->id,
            'key'       => $key
        ])->first();
        $recipient->markAnswered();
        $recipient->save();
        
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
            $question->save();
            
            if ($q['answer']['type'] == 8) {
                foreach ($q['answer']['options'] as $o) {
                    $value = new QuestionCustomValue;
                    $value->name = $o['value'];
                    $value->questionId = $question->id;
                    $value->save();
                }
            }
            
            $questionLink = new InstantFeedbackQuestion();
            $questionLink->instant_feedback_id = $instantFeedback->id;
            $questionLink->question_id = $question->id;
            $questionLink->save();
        }
    }
    
    /**
     * Processes each recipient and creates recipient records for each.
     *
     * @param   App\Models\InstantFeedback  $instantFeedback
     * @param   array                       $recipients
     * @return  void
     */
    protected function processRecipients(InstantFeedback $instantFeedback, array $recipients)
    {
        foreach ($recipients as $r) {
            $user = User::find($r['id']);
            $recipient = InstantFeedbackRecipient::make($instantFeedback, $user);
        }
    }
    
}
