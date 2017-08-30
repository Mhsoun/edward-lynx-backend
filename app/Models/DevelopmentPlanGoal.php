<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\BaseModel;
use App\Contracts\JsonHalLinking;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;


class DevelopmentPlanGoal extends BaseModel implements Scope, JsonHalLinking
{

    const DUE_THRESHOLD = 3;

    public $fillable = ['title', 'description', 'position', 'dueDate'];

    public $timestamps = false;

    protected $dates = ['dueDate'];

    protected $visible = ['id', 'categoryId', 'title', 'description', 'checked', 'position', 'dueDate', 'reminderSent'];

    /**
     * Scopes results to goals that are within the due date threshold.
     *
     * @param   Illuminate\Database\Eloquent\Builder    $query
     * @param   int                                     $dueThreshold
     * @return  Illuminate\Database\Eloquent\Builder
     */
    public function scopeDue(Builder $query, $dueThreshold = null)
    {
        $now = Carbon::now();

        if ($dueThreshold) {
            $due = Carbon::now()->addDays($dueThreshold);
        } else {
            $due = Carbon::now()->addDays(self::DUE_THRESHOLD);
        }

        return $query->where('dueDate', '>=', $now)
                     ->where('dueDate', '<=', $due);
    }

    /**
     * Scopes results to open/unchecked development plans goals only.
     *
     * @param   Illuminate\Database\Eloquent\Builder    $query
     * @return  Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen(Builder $query)
    {
        return $query->where('checked', false);
    }

    /**
     * Scopes results to expired development plan goals only.
     * 
     * @param  Illuminate\Database\Eloquent\Builder    $query
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired(Builder $query)
    {
        $now = Carbon::now();
        return $query->where('dueDate', '<=', $now);
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
     * Returns the survey category this goal is linked to.
     *
     * @return  Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function category()
    {
        return $this->hasOne(QuestionCategory::class, 'id', 'categoryId');
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
     * Returns the owner of this development plan goal.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'ownerId');
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
     * Updates this goal's checked status depending on the checked
     * status of it's child actions.
     *
     * @return  void
     */
    public function updateChecked()
    {
        $this->load('actions');
        
        if ($this->actions()->count() == 0) {
            $this->checked = false;
        } else {
            $notDone = $this->actions()
                            ->where('checked', false)
                            ->count();
            $this->checked = $notDone == 0;
        }
        
        $this->save();
        return $this->checked;
    }

    /**
     * Returns the progress percentage of this goal: from 0 to 1.
     * 
     * @return  float 
     */
    public function calculateProgress()
    {
        $checkedActions = $this->actions()->where('checked', true)->count();
        $totalActions = $this->actions()->count();

        return $checkedActions / $totalActions;
    }

    /**
     * Returns TRUE if this development plan goal hasn't still reached its
     * due date, if it has one.
     * 
     * @return  boolean
     */
    public function isValid()
    {
        if (is_null($this->dueDate)) {
            return true;
        } else {
            return $this->dueDate->isFuture();
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

    /**
     * Returns additional links for JSON-HAL links field.
     *
     * @return  array
     */
    public function jsonHalLinks()
    {
        if ($this->category) {
            return [
                'category'  => $this->category->url()
            ];
        } else {
            return [];
        }
    }

}
