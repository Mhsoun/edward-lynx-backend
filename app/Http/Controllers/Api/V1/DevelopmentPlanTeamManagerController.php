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
            $devPlan = DevelopmentPlan::find($item['id']);
            $devPlan->updateTeamAttribute([
                'position'  => $item['position'],
                'visible'   => $item['visible']
            ]);
        }

        $devPlans = DevelopmentPlan::forTeams()
                        ->where('ownerId', $currentUser->id)
                        ->get();
        foreach ($devPlans as $index => $devPlan) {
            $devPlan->updateTeamAttribute([
                'position'  => $index
            ]);
        }

        $response = ['items' => []];
        foreach ($devPlans as $devPlan) {
            $teamAttr = $devPlan->teamAttribute();
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
     * @param   App\DevelopmentPlan  $devPlan
     * @return  Illuminate\Http\Response
     */
    public function show(DevelopmentPlan $devPlan)
    {
        if (!$devPlan->isTeam()) {
            abort(404);
        }

        $devPlan = DevelopmentPlan::forTeams()
                    ->where('id', $devPlan->id)
                    ->firstOrFail();

        return response()->jsonHal($devPlan->jsonSerialize(DevelopmentPlan::SERIALIZE_TEAM_DETAILS));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\DevelopmentPlan  $devPlan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DevelopmentPlan $devPlan)
    {
        if (!$devPlan->isTeam()) {
            abort(404);
        }
        
        $this->validate($request, [
            'name'      => 'string|max:255',
            'position'  => 'integer|min:0',
            'visible'   => 'boolean'
        ]);

        $devPlan->name = $request->name;
        $devPlan->save();

        $devPlan->updateTeamAttribute($request->only('position', 'visible'));
        if ($request->has('position')) {
            DevelopmentPlan::sortTeamsByPosition($devPlan->owner);
        }

        $devPlan = DevelopmentPlan::forTeams()
                    ->where('id', $devPlan->id)
                    ->first();

        return response()->jsonHal($devPlan);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\DevelopmentPlan  $devPlan
     * @return \Illuminate\Http\Response
     */
    public function destroy(DevelopmentPlan $devPlan)
    {
        if (!$devPlan->isTeam()) {
            abort(404);
        }

        $devPlan->delete();
        DevelopmentPlan::sortTeamsByPosition($devPlan->owner);

        return response('', 204, ['Content-type' => 'application/json']);
    }

    /**
     * Serialies a development plan into its JSON equivalent.
     * 
     * @param   App\Models\DevelopmentPlan  $devPlan
     * @return  array
     */
    protected function serializeDevPlan(DevelopmentPlan $devPlan)
    {
        return [
            '_links'    => [
                'self'  => ['href' => route('api1-dev-plan-manager-teams.show', $devPlan)],
                'goals' => ['href' => route('api1-dev-plan-goals.index', $devPlan)]
            ],
            'id'        => $devPlan->id,
            'name'      => $devPlan->name,
            'ownerId'   => $devPlan->ownerId,
            'position'  => $devPlan->position,
            'checked'   => $devPlan->checked,
            'visible'   => $devPlan->visible,
            'progress'  => $devPlan->calculateProgress(),
            'goals'     => $devPlan->goals->map(function ($goal) {
                return [
                    'title'     => $goal->title,
                    'progress'  => $goal->calculateProgress()
                ];
            })
        ];
    }
}
