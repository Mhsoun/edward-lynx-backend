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
        if ($request->has('type') && $request->type == 'expired') {
            $devPlans = $user->developmentPlans
                             ->filter(function($devPlan) {
                                return $devPlan->goals()->expired()->count() > 0;
                             })
                             ->jsonSerialize();

            // Remove non-expired development plan goals.
            $now = Carbon::now();
            $devPlans = array_map(function($devPlan) use ($now) {
                $devPlan['goals'] = $devPlan['goals']->filter(function($goal) use ($now) {
                    return $goal->dueDate !== null && $goal->dueDate->lte($now);
                });
                return $devPlan;
            }, $devPlans);
        } else {
            $devPlans = $user->developmentPlans()
                             ->orderByRaw('checked ASC, createdAt DESC')
                             ->get();
        }

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
        
        // Create initial dev plan
        $devPlan = new DevelopmentPlan($request->only('name'));
        $devPlan->ownerId = $user->id;
        $devPlan->save();

        // Process development plan goals
        foreach ($request->goals as $g) {
            $attributes = [
                'title'         => sanitize($g['title']),
                'description'   => empty($g['description']) ? '' : sanitize($g['description']),
                'dueDate'       => empty($g['dueDate']) ? null : dateFromIso8601String($g['dueDate']),
                'position'      => $g['position'],
            ];

            // Process category ID for goal.
            if (!empty($g['categoryId'])) {
                $category = QuestionCategory::find($g['categoryId']);
                if ($user->can('view', $category)) {
                    $attributes['categoryId'] = $g['categoryId'];
                }
            }

            $goal = $devPlan->goals()->create($attributes);
            $goal->categoryId = !empty($attributes['categoryId']) ? $attributes['categoryId'] : null;
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
        $currentUser = $request->user();
        foreach($currentUser->unreadNotifications() as $notification) {
            if ($notification->data['devPlanId'] == $devPlan->id) {
                $notification->markAsRead();
            }
        }

        return response()->jsonHal($devPlan);
    }
    
}
