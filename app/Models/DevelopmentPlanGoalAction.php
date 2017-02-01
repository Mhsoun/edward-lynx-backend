<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DevelopmentPlanGoalAction extends Model
{
    
    public $timestamps = false;
    
    public $fillable = ['title', 'checked', 'position'];
    
    protected $visible = ['id', 'title', 'checked', 'position'];
    
    /**
     * Returns the parent goal of this action.
     *
     * @return  Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function goal()
    {
        return $this->belongsTo(DevelopmentPlanGoal::class, 'goalId');
    }
    
}
