<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DevelopmentPlanGoalActionController extends Controller
{
    
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
