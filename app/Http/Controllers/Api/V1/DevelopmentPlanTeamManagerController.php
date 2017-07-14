<?php

namespace App\Http\Controllers\Api\V1;

use App\SurveyTypes;
use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
use App\Models\QuestionCategory;
use App\Models\SurveySharedReport;
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
        $devPlans = TeamDevelopmentPlan::where('ownerId', $currentUser->id)->get();

        return response()
            ->jsonHal($devPlans)
            ->summarize();
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
            'name'          => 'required|string|max:255',
            'lang'          => 'required|in:en,sv,fi'
        ]);

        $currentUser = $request->user();
        
        $devPlan = TeamDevelopmentPlan::make($currentUser, $request->name, $request->lang);

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

        $response = [];
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

    /**
     * Manager reports endpoint.
     * 
     * @param   Illuminate\Http\Request     $request
     * @return  App\Http\JsonHalResponse
     */
    public function reports(Request $request)
    {
        $currentUser = $request->user();
        $ssr = SurveySharedReport::where('userId', $currentUser->id)->get();
        $json = SurveySharedReport::json($ssr);

        return response()->jsonHal($json);
    }
}
