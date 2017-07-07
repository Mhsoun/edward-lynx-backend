<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\QuestionCategory;
use App\Models\TeamDevelopmentPlan;
use App\Http\Controllers\Controller;
use App\Models\DevelopmentPlanTeamAttribute;

class DevelopmentPlanTeamManagerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return  Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $devPlans = DevelopmentPlan::forTeams()
                        ->where('ownerId', $currentUser->id)
                        ->get()
                        ->map(function($item) {
                            return $this->serializeDevPlan($item);
                        })
                        ->toArray();

        return response()->jsonHal($devPlans);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param   Illuminate\Http\Request  $request
     * @return  Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'categoryId'    => 'required|integer|exists:question_categories,id'
        ]);

        $currentUser = $request->user();
        $category = QuestionCategory::find($request->categoryId);

        // Check if the current user has access to the category.
        if (!$category->owner->colleagueOf($currentUser) && $category->isSurvey) {
            abort(400);
        }

        // Return an existing development plan if it already exists.
        if ($devPlan = $currentUser->teamDevelopmentPlans()->where('categoryId', $category->id)->first()) {
            return createdResponse(['Location' => route('api1-dev-plan-manager-teams.show', $devPlan)]);
        }

        TeamDevelopmentPlan::shift($currentUser);

        $devPlan = new TeamDevelopmentPlan;
        $devPlan->ownerId = $currentUser->id;
        $devPlan->categoryId = $category->id;
        $devPlan->save();

        TeamDevelopmentPlan::sort($currentUser);

        return createdResponse(['Location' => route('api1-dev-plan-manager-teams.show', $devPlan)]);
    }

    /**
     * Sort multiple team development plans.
     * 
     * @param   Illuminate\Http\Request  $request
     * @return  App\Http\JsonHalResponse
     */
    public function sort(Request $request)
    {
        $currentUser = $request->user();

        $this->validate($request, [
            'items'             => 'required|array',
            'items.*.id'        => 'required|exists:development_plans',
            'items.*.position'  => 'required|integer|min:0',
            'items.*.visible'   => 'required|boolean'
        ]);

        foreach ($request->items as $item) {
            $devPlan = TeamDevelopmentPlan::find($item['id']);
            $devPlan->fill([
                'position'  => $item['position'],
                'visible'   => $item['visible']
            ]);
            $devPlan->save();
        }

        TeamDevelopmentPlan::sort($currentUser);

        $devPlans = $currentUser->teamDevelopmentPlans();
        foreach ($devPlans as $devPlan) {
            $response['items'][] = [
                'id'        => $devPlan->id,
                'position'  => $teamAttr->position,
                'visible'   => $teamAttr->visible
            ];
        }

        return response()->jsonHal($response);
    }

    /**
     * Display the specified resource.
     *
     * @param   App\TeamDevelopmentPlan  $devPlan
     * @return  Illuminate\Http\Response
     */
    public function show(TeamDevelopmentPlan $devPlan)
    {
        return response()->jsonHal($devPlan);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param   App\Models\DevelopmentPlan  $devPlan
     * @return  Illuminate\Http\Response
     */
    public function destroy(TeamDevelopmentPlan $devPlan)
    {
        $devPlan->delete();
        return response('', 204, ['Content-type' => 'application/json']);
    }
}
