<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
use App\Http\Controllers\Controller;

class DevelopmentPlanTeamManagerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $devPlans = DevelopmentPlan::team()
                        ->where('ownerId', $currentUser->id)
                        ->orderBy('createdAt', 'desc')
                        ->get();

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
            'name'  => 'required|string|max:255'
        ]);

        $currentUser = $request->user();

        $devPlan = new DevelopmentPlan($request->only('name'));
        $devPlan->ownerId = $currentUser->id;
        $devPlan->team = true;
        $devPlan->save();

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
        if (!$devPlan->team) {
            abort(404);
        }

        return response()->jsonHal($devPlan)
                         ->withLinks([
                            'self'  => route('api1-dev-plan-manager-teams.show', $devPlan)
                         ]);
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
        if (!$devPlan->team) {
            abort(404);
        }
        
        $this->validate($request, [
            'name'  => 'required|string|max:255'
        ]);

        $devPlan->fill($request->only('name'));
        $devPlan->save();

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
        if (!$devPlan->team) {
            abort(404);
        }

        $devPlan->delete();

        return response('', 204, ['Content-type' => 'application/json']);
    }
}
