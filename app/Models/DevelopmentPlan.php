<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevelopmentPlan extends Model
{
    
    /**
     * Returns the user who owns this development plan.
     *
     * @return  App\Models\User
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'ownerId');
    }
    
    /**
     * Returns the user who this development plan is for.
     *
     * @return  App\Models\User
     */
    public function target()
    {
        return $this->belongsTo(User::class, 'targetId');
    }

    /**
     * Returns the goals of this development plan.
     *
     * @return  App\Models\User
     */
    public function goals()
    {
        return $this->hasMany(DevelopmentPlanGoal::class, 'developmentPlanId')
                    ->orderBy('position', 'asc');
    }
    
}
