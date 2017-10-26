<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
use App\Models\DevelopmentPlanGoal;
use App\Http\Controllers\Controller;
use App\Models\DevelopmentPlanGoalAction;
use App\Exceptions\InvalidOperationException;

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
            'position'  => 'required|integer|min:0'
        ]);

        $attributes = [
            'title'     => strip_tags($request->input('title')),
            'position'  => intval($request->position),
        ];
        $action = $goal->actions()->create($attributes);

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

        if ($request->has('checked') && !$goal->isValid()) {
            throw new InvalidOperationException('Development plan goal has reached its due date.');
        }
        
        $fields = ['title', 'checked', 'position'];
        $toStrip = ['title'];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $value = in_array($field, $toStrip) ? strip_tags($request->input($field)) : $request->input($field);
                $action->{$field} = $value;
            }
        }
        $action->save();
        $action = $action->fresh();
        
        return response()->jsonHal($action);
    }

    /**
     * Delete a goal action.
     *
     * @param   Illuminate\Http\Request                 $request
     * @param   App\Models\DevelopmentPlan              $devPlan
     * @param   App\Models\DevelopmentPlanGoal          $goal
     * @param   App\Models\DevelopmentPlanGoalAction    $action
     * @return  App\Http\JsonHalResponse
     */
    public function delete(Request $request, DevelopmentPlan $devPlan, DevelopmentPlanGoal $goal, DevelopmentPlanGoalAction $action)
    {
        $action->delete();
        return response('', 204, ['Content-type' => 'application/json']);
    }

}
