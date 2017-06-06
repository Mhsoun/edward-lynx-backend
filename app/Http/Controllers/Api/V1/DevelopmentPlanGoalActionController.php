<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
use App\Models\DevelopmentPlanGoal;
use App\Http\Controllers\Controller;
use App\Models\DevelopmentPlanGoalAction;

class DevelopmentPlanGoalActionController extends Controller
{

    /**
     * Create a new goal action.
     * 
     * @param  Illuminate\Http\Request          $request
     * @param  App\Models\DevelopmentPlan       $devPlan
     * @param  App\Models\DevelopmentPlanGoal   $goal
     * @return Illuminate\Http\Response
     */
    public function create(Request $request, DevelopmentPlan $devPlan, DevelopmentPlanGoal $goal)
    {
        $this->validate($request, [
            'title'     => 'required|string|max:255',
            'position'  => 'integer|min:0'
        ]);

        $action = $goal->actions()->create($request->only('title', 'position'));
        return createdResponse(['Location' => route('api1-dev-plan', $devPlan)]);
    }

    /**
     * Update a goal action's details.
     *
     * @param   Illuminate\Http\Request                 $request
     * @param   App\Models\DevelopmentPlan              $devPlan
     * @param   App\Models\DevelopmentPlanGoal          $goal
     * @param   App\Models\DevelopmentPlanGoalAction    $action
     * @return  App\Http\JsonHalResponse
     */
    public function update(Request $request, DevelopmentPlan $devPlan, DevelopmentPlanGoal $goal, DevelopmentPlanGoalAction $action)
    {
        $this->validate($request, [
            'title'     => 'string|max:255',
            'checked'   => 'boolean',
            'position'  => 'integer|min:0'
        ]);
        
        $action->fill($request->only('title', 'description', 'position'));
        $action->checked = $request->checked;
        $action->save();
        $action = $action->fresh();
        
        return response()->jsonHal($action);
    }

}
