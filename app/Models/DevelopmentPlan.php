<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Contracts\Routable;

class DevelopmentPlan extends BaseModel implements Routable
{
    
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    public $fillable = ['name'];
    
    protected $visible = ['id', 'name', 'createdAt', 'updatedAt'];
    
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
    
    /**
     * Returns additional links for JSON-HAL links field.
     *
     * @return  array
     */
    public function jsonHalLinks()
    {
        return [
            'owner' => $this->owner->url()
        ];
    }
    
}
