<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;


class DevelopmentPlanGoal extends Model implements Scope
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
    
    /**
     * Goals are sorted by their position by default.
     *
     * @param   Illuminate\Database\Eloquent\Builder    $builder
     * @param   Illuminate\Database\Eloquent\Model      $model
     * @return  void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->orderBy('position', 'asc');
    }
    
}
