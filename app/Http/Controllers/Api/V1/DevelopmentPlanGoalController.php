<?php

namespace App\Http\Controllers\Api\v1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
use App\Models\DevelopmentPlanGoal;
use App\Http\Controllers\Controller;

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
        $this->validate($request, [
            'title'         => 'string|max:255',
            'description'   => 'string',
            'checked'       => 'boolean',
            'position'      => 'integer|min:0'
        ]);
            
        $goal->fill($request->only('title', 'description', 'position'));
        $goal->checked = $request->checked;
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
