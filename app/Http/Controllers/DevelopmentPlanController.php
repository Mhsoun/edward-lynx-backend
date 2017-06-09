<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DevelopmentPlan;

class DevelopmentPlanController extends Controller
{
    
    public function show(Request $request, DevelopmentPlan $devPlan)
    {
        return response('Launching Edward Lynx Mobile app...');
    }

}
