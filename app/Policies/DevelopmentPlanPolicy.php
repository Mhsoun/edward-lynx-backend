<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DevelopmentPlan;
use Illuminate\Auth\Access\HandlesAuthorization;

class DevelopmentPlanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the developmentPlan.
     *
     * @param   App\User             $user
     * @param   App\DevelopmentPlan  $devPlan
     * @return  mixed
     */
    public function view(User $user, DevelopmentPlan $devPlan)
    {
        if ($devPlan->ownerId == $user->id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can create DevelopmentPlans.
     *
     * @param   App\User  $user
     * @return  mixed
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the developmentPlan.
     *
     * @param   App\User                $user
     * @param   App\DevelopmentPlan     $devPlan
     * @return  mixed
     */
    public function update(User $user, DevelopmentPlan $devPlan)
    {
        return $devPlan->ownerId == $user->id;
    }

    /**
     * Determine whether the user can delete the developmentPlan.
     *
     * @param   App\User            $user
     * @param   App\DevelopmentPlan $devPlan
     * @return  mixed
     */
    public function delete(User $user, DevelopmentPlan $devPlan)
    {
        return $devPlan->ownerId == $user->id;
    }
}
