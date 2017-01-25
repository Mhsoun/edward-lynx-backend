<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevelopmentPlanGoal extends Model
{
    
    public $fillable = ['title', 'description', 'checked', 'position', 'dueDate'];
    
    public $timestamps = false;
    
    /**
     * Returns the development plan this goal is under.
     *
     * @param   App\Models\DevelopmentPlan
     */
    public function developmentPlan()
    {
        return $this->belongsTo(DevelopmentPlan::class, 'developmentPlanId');
    }
    
}
