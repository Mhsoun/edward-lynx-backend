<?php

namespace App\Http\Controllers\Api\V1;

use DB;
use App\Models\User;
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
            $users = $currentUser
                        ->colleagues()
                        ->where('id', '!=', $currentUser->id)
                        ->filter(function($user) {
                            return $user->developmentPlans()->where('shared', true)->count() > 0;
                        });
            $users = $users->map(function($user) use ($currentUser) {
                return array_merge([
                    'managed' => $user->managedBy($currentUser)
                ], $user->jsonSerialize());
            })->toArray();
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
            })->filter(function($item) {
                return count($item['devPlans']) > 0;
            })->toArray();
        }

        return response()->jsonHal($users);
    }

    /**
     * Links users to the current user.
     * 
     * @param   Illuminate\Http\Request   $request
     * @return  App\Http\JsonHalResponse
     */
    public function set(Request $request)
    {
        $currentUser = $request->user();
        $this->validate($request, [
            'users'         => 'array',
            'users.*.id'    => 'required|exists:users|colleague|sharing_dev_plans|not_in:'. $currentUser->id
        ]);

        $values = array_map(function($v) {
            return $v['id'];
        }, $request->users);
        $existing = $currentUser->managedUsers->map(function($user) {
            return $user->pivot->userId;
        })->toArray();

        $toDelete = array_diff($existing, $values);
        $toAdd = array_diff($values, $existing);

        DB::table('managed_users')
            ->where('managerId', $currentUser->id)
            ->whereIn('userId', $toDelete)
            ->delete();

        foreach ($toAdd as $userId) {
            DB::table('managed_users')
                ->insert([
                    'managerId' => $currentUser->id,
                    'userId'    => $userId
                ]);
        }

        return response()->jsonHal([
            'users' => array_map(function($id) {
                return ['id' => $id];
            }, $values)
        ]);
    }

}
