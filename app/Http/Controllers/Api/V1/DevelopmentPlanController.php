<?php

namespace App\Http\Controllers\Api\V1;

use App\Sanitizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
use App\Models\DevelopmentPlanGoal;
use App\Http\Controllers\Controller;

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
        $devPlans = $user->developmentPlans;
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
        return response('', 201, ['Location' => $url]);
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
    
    /**
     * Updates a development plan goal's details.
     *
     * @param   Illuminate\Http\Request         $request
     * @param   App\Models\DevelopmentPlan      $devPlan
     * @param   App\Models\DevelopmentPlanGoal  $goal
     * @return  App\Http\JsonHalResponse
     */
    public function updateGoal(Request $request, DevelopmentPlan $devPlan, DevelopmentPlanGoal $goal)
    {
        $this->validate($request, [
            'title'         => 'string|max:255',
            'description'   => 'string',
            'position'      => 'integer|min:0'
        ]);
            
        $goal->fill($request->all());
        $goal->save();
        
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
    public function deleteGoal(Request $request, DevelopmentPlan $devPlan, DevelopmentPlanGoal $goal)
    {
        $goal->delete();
        return response('', 204);
    }
}
