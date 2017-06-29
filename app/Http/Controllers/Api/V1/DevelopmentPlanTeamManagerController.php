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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\DevelopmentPlan  $developmentPlan
     * @return \Illuminate\Http\Response
     */
    public function show(DevelopmentPlan $developmentPlan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\DevelopmentPlan  $developmentPlan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DevelopmentPlan $developmentPlan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\DevelopmentPlan  $developmentPlan
     * @return \Illuminate\Http\Response
     */
    public function destroy(DevelopmentPlan $developmentPlan)
    {
        //
    }
}
