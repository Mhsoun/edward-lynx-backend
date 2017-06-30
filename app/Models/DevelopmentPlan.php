<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Contracts\Routable;
use App\Contracts\JsonHalLinking;
use Illuminate\Database\Eloquent\Builder;

class DevelopmentPlan extends BaseModel implements Routable, JsonHalLinking
{
    
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    public $fillable = ['name', 'shared'];
    
    protected $visible = ['id', 'name', 'checked', 'shared', 'createdAt', 'updatedAt'];

    /**
     * Returns the API url to this development plan.
     *
     * @param   string  $prefix
     * @return  string
     */
    public function url()
    {
        return route('api1-dev-plan', $this);
    }

    /**
     * Returns additional JSON-HAL links.
     * 
     * @return  array
     */
    public function jsonHalLinks()
    {
        return [
            'goals' => route('api1-dev-plan-goals.index', $this)
        ];
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
     * Scopes results to team development plans only.
     * 
     * @param   Illuminate\Database\Eloquent\Builder    $query
     * @return  Illuminate\Database\Eloquent\Builder
     */
    public function scopeTeam(Builder $query)
    {
        return $query->where('team', true);
    }
    
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
     * Returns the goals of this development plan.
     *
     * @return  App\Models\User
     */
    public function goals()
    {
        return $this->hasMany(DevelopmentPlanGoal::class, 'developmentPlanId')
                    ->orderBy('position', 'asc');
    }

    /**
     * Returns the team attribute model for this development plan.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function team()
    {
        return $this->hasOne(DevelopmentPlanAttribute::class, 'developmentPlanId');
    }

    /**
     * Updates this dev plan's checked status depending on the checked
     * status of it's child goals.
     *
     * @return  void
     */
    public function updateChecked()
    {
        $this->load('goals');

        if ($this->goals()->count() == 0) {
            $this->checked = false;
        } else {
            $notDone = $this->goals()
                            ->where('checked', false)
                            ->count();
            $this->checked = $notDone == 0;
        }
        
        $this->save();
        return $this->checked;
    }
    
    /**
     * Updates goal positions, used when the position attributes
     * are not in sequence.
     *
     * @return  void
     */
    public function updateGoalPositions()
    {
        foreach ($this->goals as $index => $goal) {
            $goal->position = $index;
            $goal->save();
        }
    }
    
    /**
     * Include this development plan's goals when serializing to JSON.
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();
        $json['goals'] = $this->goals;
        return $json;
    }
    
}
