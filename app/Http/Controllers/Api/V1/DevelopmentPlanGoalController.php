<?php

namespace App\Http\Controllers\Api\v1;

use DateTime;
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
            'title'                 => 'required|string|max:255',
            'description'           => 'string',
            'position'              => 'required|integer|min:0',
            'dueDate'               => 'isodate',
            'actions'               => 'required|array',
            'actions.*.title'       => 'required|string|max:255',
            'actions.*.position'    => 'required|integer|min:0'
        ]);

        $currentUser = $request->user();

        $goal = new DevelopmentPlanGoal($request->only('title', 'description', 'position'));
        $goal->developmentPlanId = $devPlan->id;
        $goal->ownerId = $currentUser->id;

        // Strip html tags
        $toStrip = ['title', 'description'];
        foreach ($toStrip as $key) {
            $goal->{$key} = strip_tags($goal->{$key});
        }

        if ($request->has('dueDate')) {
            $goal->dueDate = dateFromIso8601String($request->dueDate);
        }

        if ($request->has('categoryId')) {
            $category = QuestionCategory::find($request->categoryId);
            if ($currentUser->can('view', $category)) {
                $goal->categoryId = $category->id;
            } else {
                throw new CustomValidationException([
                    'categoryId' => ['Invalid category id.']
                ]);
            }
        }

        $goal->save();

        foreach ($request->actions as $action) {
            $attributes = [
                'title'     => strip_tags($action['title']),
                'position'  => intval($action['position']),
            ];
            $goal->actions()->create($attributes);
        }

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
            'checked'       => 'boolean'
        ]);

        $fields = ['title', 'description', 'position', 'checked'];
        $toStrip = ['title', 'description'];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $value = in_array($field, $toStrip) ? strip_tags($request->input($field)) : $request->input($field);
                $goal->{$field} = $value;
            }
        }

        if ($request->exists('dueDate')) {
            if ($request->dueDate) {
                $this->validate($request, ['dueDate' => 'isodate']);
                $goal->dueDate = dateFromIso8601String($request->dueDate);
            } else {
                $goal->dueDate = null;
            }
        }

        if ($request->exists('categoryId')) {
            if ($request->categoryId) {
                $category = QuestionCategory::find($request->categoryId);
                if ($currentUser->can('view', $category)) {
                    $goal->categoryId = $request->categoryId;
                } else {
                    throw new CustomValidationException([
                        'categoryId' => ['Invalid category id.']
                    ]);
                }
            } else { // categoryId is null, remove it.
                $goal->categoryId = null;
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
