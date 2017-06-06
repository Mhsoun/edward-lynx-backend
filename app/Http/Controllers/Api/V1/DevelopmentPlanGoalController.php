<?php

namespace App\Http\Controllers\Api\v1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
use App\Models\QuestionCategory;
use App\Models\DevelopmentPlanGoal;
use App\Http\Controllers\Controller;
use App\Exceptions\CustomValidationException;

class DevelopmentPlanGoalController extends Controller
{

    /**
     * Creates a new development plan goal.
     * 
     * @param  Illuminate\Http\Request          $request
     * @param  App\Models\DevelopmentPlan       $devPlan
     * @param  App\Models\DevelopmentPlanGoal   $goal
     * @return Illuminate\Http\Response
     */
    public function create(Request $request, DevelopmentPlan $devPlan)
    {
        $this->validate($request, [
            'title'                 => 'string|max:255',
            'description'           => 'string',
            'position'              => 'integer|min:0',
            'dueDate'               => 'isodate',
            'actions'               => 'required|array',
            'actions.*.title'       => 'required|string|max:255',
            'actions.*.position'    => 'required|integer|min:0'
        ]);

        $attributes = $request->only('title', 'description', 'position', 'dueDate');
        $attributes['dueDate'] = Carbon::parse($attributes['dueDate']);

        $goal = $devPlan->goals()
                        ->create($attributes);
        foreach ($request->actions as $action) {
            $goal->actions()->create($action);
        }

        $devPlan->checked = false;
        $devPlan->save();

        return createdResponse(['Location' => route('api1-dev-plan', $devPlan)]);
    }
    
    /**
     * Updates a development plan goal's details.
     *
     * @param   Illuminate\Http\Request         $request
     * @param   App\Models\DevelopmentPlan      $devPlan
     * @param   App\Models\DevelopmentPlanGoal  $goal
     * @return  App\Http\JsonHalResponse
     */
    public function update(Request $request, DevelopmentPlan $devPlan, DevelopmentPlanGoal $goal)
    {
        $currentUser = $request->user();

        $this->validate($request, [
            'title'         => 'string|max:255',
            'description'   => 'string',
            'position'      => 'integer|min:0',
            'dueDate'       => 'isodate'
        ]);
            
        $goal->fill($request->only('title', 'description', 'position'));
        $goal->checked = $request->checked;

        if ($request->has('dueDate')) {
            $goal->dueDate = Carbon::parse($request->dueDate);
        }

        if ($request->has('categoryId')) {
            $category = QuestionCategory::find($request->categoryId);
            if ($currentUser->can('view', $category)) {
                $goal->categoryId = $request->categoryId;
            } else {
                throw new CustomValidationException([
                    'categoryId' => ['Invalid category id.']
                ]);
            }
        }

        $goal->save();
        $goal = $goal->fresh();
        
        return response()->jsonHal($goal);
    }
    
    /**
     * Delete a development plan goal.
     *
     * @param   Illuminate\Http\Request         $request
     * @param   App\Models\DevelopmentPlan      $devPlan
     * @param   App\Models\DevelopmentPlanGoal  $goal
     * @return  Illuminate\Http\Response
     */
    public function destroy(Request $request, DevelopmentPlan $devPlan, DevelopmentPlanGoal $goal)
    {
        $goal->delete();
        return response('', 204);
    }

}
