<?php

namespace App\Http\Controllers\Api\V1;

use App\Sanitizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
use Illuminate\Validation\Rule;
use App\Models\QuestionCategory;
use App\Models\DevelopmentPlanGoal;
use App\Http\Controllers\Controller;
use App\Models\DevelopmentPlanGoalAction;
use App\Exceptions\CustomValidationException;

class DevelopmentPlanController extends Controller
{
    
    /**
     * Returns the current user's development plans.
     *
     * @param   Illuminate\Http\Request
     * @return  App\Http\JsonHalResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $devPlans = $user->developmentPlans()
                         ->orderByRaw('checked ASC, createdAt DESC')
                         ->get();
        return response()->jsonHal($devPlans);
    }
    
    /**
     * Create a development plan.
     *
     * @param   Illuminate\Http\Request
     * @return  Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'name'                          => 'required|string|max:255',
            'categoryId'                    => [
                'integer',
                Rule::exists('question_categories', 'id')->where(function ($query) {
                    $query->where('isSurvey', true);
                })
            ],
            'goals'                         => 'required|array',
            'goals.*.title'                 => 'required|string|max:255',
            'goals.*.description'           => 'string',
            'goals.*.dueDate'               => 'isodate',
            'goals.*.position'              => 'required|integer|min:0',
            'goals.*.actions'               => 'required|array',
            'goals.*.actions.*.title'       => 'required|string|max:255',
            'goals.*.actions.*.position'    => 'required|integer|min:0'
        ]);
            
        $user = $request->user();
        
        // Make sure the category is visible for the current user
        if ($request->has('categoryId')) {
            $category = QuestionCategory::find($request->categoryId);
            if (!$user->can('view', $category)) {
                throw new CustomValidationException([
                    'categoryId' => ['Invalid category id.']
                ]);
            }
        }
        
        // Create initial dev plan
        $devPlan = new DevelopmentPlan($request->all());
        $devPlan->ownerId = $user->id;
        $devPlan->save();

        // Process development plan goals
        foreach ($request->goals as $g) {
            $goal = $devPlan->goals()->create([
                'title'         => sanitize($g['title']),
                'description'   => empty($g['description']) ? '' : sanitize($g['description']),
                'dueDate'       => empty($g['dueDate']) ? null : Carbon::parse($g['dueDate']),
                'position'      => $g['position']
            ]);
            $goal->save();
            
            // Create actions under each goal.
            foreach ($g['actions'] as $a) {
                $action = $goal->actions()->create([
                    'title'     => sanitize($a['title']),
                    'position'  => $a['position']
                ]);
                $action->save();
            }
            
            // Ensure goal actions positions are in sequence.
            $goal->updateActionPositions();
        }
        
        // Ensure goal positions are in sequence.
        $devPlan->updateGoalPositions();

        $url = route('api1-dev-plan', ['devPlan' => $devPlan]);
        return createdResponse(['Location' => $url]);
    }
    
    /**
     * Displays development plan details.
     *
     * @param   Illuminate\Http\Request     $request
     * @param   App\Models\DevelopmentPlan  $devPlan
     * @return  App\Http\JsonHalResponse
     */
    public function show(Request $request, DevelopmentPlan $devPlan)
    {
        return response()->jsonHal($devPlan);
    }
    
}
