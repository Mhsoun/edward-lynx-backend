<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'      => 'required|string|max:255'
        ]);

        $currentUser = $request->user();

        $devPlan = new DevelopmentPlan($request->only('name'));
        $devPlan->ownerId = $currentUser->id;
        $devPlan->save();

        DevelopmentPlan::insertAsTeamDevelopmentPlan($devPlan);

        return createdResponse(['Location' => route('api1-dev-plan-manager-teams.show', $devPlan)]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\DevelopmentPlan  $devPlan
     * @return \Illuminate\Http\Response
     */
    public function show(DevelopmentPlan $devPlan)
    {
        if (!$devPlan->isTeam()) {
            abort(404);
        }

        $devPlan = DevelopmentPlan::forTeams()
                    ->where('id', $devPlan->id)
                    ->first();

        return response()->jsonHal($this->serializeDevPlan($devPlan));
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
            'visible'   => $devPlan->visible
        ];
    }
}
