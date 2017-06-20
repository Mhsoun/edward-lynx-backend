<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DevelopmentPlanManagerController extends Controller
{
    
    /**
     * Returns the list of users managed by the current user.
     * 
     * @param   Illuminate\Http\Request  $request
     * @return  App\Http\JsonHalResponse
     */
    public function users(Request $request)
    {
        $currentUser = $request->user();

        if ($request->type == 'sharing') {
            $users = $currentUser->colleagues()->filter(function($user) {
                return $user->developmentPlans()->where('shared', true)->count() > 0;
            });
        } else {
            $users = $currentUser->managedUsers->map(function($user) {
                return [
                    '_links'    => [
                        '_self'     => $user->url()
                    ],
                    'id'        => $user->id,
                    'name'      => $user->name,
                    'devPlans'  => $user->developmentPlans()->where('shared', true)->get()
                ];
            })->toArray();
        }

        return response()->jsonHal($users);
    }

}
