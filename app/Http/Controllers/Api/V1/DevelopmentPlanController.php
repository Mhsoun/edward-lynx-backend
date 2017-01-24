<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
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
    }
    
}
