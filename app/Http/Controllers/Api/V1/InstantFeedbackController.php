<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Models\Question;
use Illuminate\Http\Request;
use App\Models\InstantFeedback;
use App\Models\QuestionCategory;
use App\Models\QuestionCustomValue;
use App\Http\Controllers\Controller;

class InstantFeedbackController extends Controller
{
    
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
            'questions.*.answer.*.value'        => 'string'
        ]);
            
        $instantFeedback = new InstantFeedback($request->all());
        $instantFeedback->user_id = $request->user()->id;
        $instantFeedback->save();
        
        $this->processQuestions($request->user(), $instantFeedback, $request->questions);
        
        $url = route('api1-instant-feedback', ['instantFeedback' => $instantFeedback]);
        return response('', 201, ['Location' => $url]);
    }
    
    public function show(Request $request, InstantFeedback $instantFeedback)
    {
        return response()->jsonHal($instantFeedback);
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
        }
    }
    
}
