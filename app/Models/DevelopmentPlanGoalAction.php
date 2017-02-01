<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class DevelopmentPlanGoalAction extends BaseModel implements Scope
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
    
    /**
     * Actions are sorted by their position by default.
     *
     * @param   Illuminate\Database\Eloquent\Builder    $builder
     * @param   Illuminate\Database\Eloquent\Model      $model
     * @return  void
     */
    public function apply(Builder $builder, EloquentModel $model)
    {
        $builder->orderBy('position', 'asc');
    }
    
}
