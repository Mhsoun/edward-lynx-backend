<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DevelopmentPlan;

class DevelopmentPlanPolicy extends Policy
{

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
        } elseif ($this->administer($user, $devPlan) || $this->supervise($user, $devPlan)) {
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
        if ($user->isA(User::ADMIN) || $user->isA(User::PARTICIPANT)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can link/manage development plans to
     * his/her user account.
     * 
     * @param   App\Models\User  $user
     * @return  boolean
     */
    public function link(User $user)
    {
        return ($user->isA(User::SUPERVISOR) || $user->isA(User::ADMIN));
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
        if ($this->administer($user, $devPlan)) {
            return true;
        } elseif ($devPlan->ownerId == $user->id) {
            return true;
        } else {
            return false;
        }
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
        if ($this->administer($user, $devPlan)) {
            return true;
        } elseif ($devPlan->ownerId == $user->id) {
            return true;
        } else {
            return false;
        }
    }
}
