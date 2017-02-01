<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;


class DevelopmentPlanGoal extends BaseModel implements Scope
{
    
    const DUE_THRESHOLD = 2;
    
    public $fillable = ['title', 'description', 'position', 'dueDate'];
    
    public $timestamps = false;
    
    protected $dates = ['dueDate'];
    
    protected $visible = ['id', 'title', 'description', 'checked', 'position', 'dueDate', 'reminderSent'];

    /**
     * Scopes results to goals that are within the due date threshold.
     *
     * @param   Illuminate\Database\Eloquent\Builder    $query
     * @return  Illuminate\Database\Eloquent\Builder
     */
    public function scopeDue(Builder $query)
    {
        $now = Carbon::now();
        $due = Carbon::now()->addDays(self::DUE_THRESHOLD);
        return $query->whereDate('dueDate', '>=', $now)
                     ->whereDate('dueDate', '<=', $due);
    }
    
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
     * Returns the actions under this goal.
     *
     * @param   Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function actions()
    {
        return $this->hasMany(DevelopmentPlanGoalAction::class, 'goalId')
                    ->orderBy('position', 'asc');
    }
    
    /**
     * Goals are sorted by their position by default.
     *
     * @param   Illuminate\Database\Eloquent\Builder    $builder
     * @param   Illuminate\Database\Eloquent\Model      $model
     * @return  void
     */
    public function apply(Builder $builder, EloquentModel $model)
    {
        $builder->orderBy('position', 'asc');
    }
    
    /**
     * Updates action positions, used when the position attributes
     * are not in sequence.
     *
     * @return  void
     */
    public function updateActionPositions()
    {
        foreach ($this->actions as $index => $action) {
            $action->position = $index;
            $action->save();
        }
    }
    
    /**
     * Fixes null dueDates which is parsed as the current date time when
     * serialized to JSON.
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();
        $json['actions'] = $this->actions;
            
        if (!$this->attributes['dueDate']) {
            $json['dueDate'] = null;
        }
        return $json;
    }
    
}
