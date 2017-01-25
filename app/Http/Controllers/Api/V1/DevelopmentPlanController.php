<?php

namespace App\Http\Controllers\Api\V1;

use App\Sanitizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;
use App\Http\Controllers\Controller;

class DevelopmentPlanController extends Controller
{
    
    /**
     * Returns the current user's development plans.
     *
     * @param   Illuminate\Http\Request
     * @return  App\Http\HalResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        return response()->jsonHal($user->developmentPlans());
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
            'name'                  => 'required|string|max:255',
            'target'                => 'required|integer|exists:users,id',
            'goals'                 => 'required|array',
            'goals.*.title'         => 'required|string|max:255',
            'goals.*.description'   => 'string',
            'goals.*.dueDate'       => 'isodate'
        ]);
            
        $user = $request->user();

        $devPlan = new DevelopmentPlan($request->all());
        $devPlan->save();

        foreach ($request->goals as $g) {
            $goal = $devPlan->goals->create([
                'title'         => Sanitizer::sanitize($g['title']),
                'description'   => Sanitizer::sanitize($g['description']),
                'dueDate'       => Carbon::parse($g['dueDate'])
            ]);
            $goal->save();
        }

        $url = route('api1-dev-plan', ['devPlan' => $devPlan]);
        return response('', 201, ['Location' => $url]);
    }
    
    /**
     * Displays development plan details.
     *
     * @param   Illuminate\Http\Request     $request
     * @param   App\Models\DevelopmentPlan  $devPlan
     * @return  App\Http\HalResponse
     */
    public function show(Request $request, DevelopmentPlan $devPlan)
    {
        return response()->jsonHal($devPlan);
    }
}
