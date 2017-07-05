<?php

namespace App\Models;

use DB;
use App\Models\BaseModel;
use App\Contracts\Routable;
use App\Contracts\JsonHalLinking;
use Illuminate\Database\Eloquent\Builder;

class DevelopmentPlan extends BaseModel implements Routable, JsonHalLinking
{
    
    const SERIALIZE_NORMAL = 0;
    const SERIALIZE_TEAM_DETAILS = 2;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    public $fillable = ['name', 'shared'];
    
    protected $visible = ['id', 'name', 'checked', 'shared', 'createdAt', 'updatedAt'];

    /**
     * Sort team development plans by position.
     * 
     * @param   App\Models\User  $owner
     * @return  void
     */
    public static function sortTeamsByPosition(User $owner)
    {
        $devPlans = $owner->developmentPlans()
                          ->forTeams()
                          ->orderBy('position', 'ASC')
                          ->get();

        foreach ($devPlans as $index => $devPlan) {
            $devPlan->updateTeamAttribute(['position' => $index]);
        }
    }

    /**
     * Creates a team development plan.
     * 
     * @param   App\Models\DevelopmentPlan   $devPlan
     * @return  App\Models\DevelopmentPlan
     */
    public static function insertAsTeamDevelopmentPlan(DevelopmentPlan $devPlan)
    {
        if ($devPlan->isTeam()) {
            return $devPlan;
        }

        $devPlans = DB::table('development_plan_team_attributes')
                        ->select('development_plans.id', 'development_plan_team_attributes.position')
                        ->join('development_plans', 'development_plan_team_attributes.developmentPlanId', '=', 'development_plans.id')
                        ->where('development_plans.ownerId', $devPlan->ownerId)
                        ->get();

        foreach ($devPlans as $dp) {
            DB::table('development_plan_team_attributes')
                ->where('developmentPlanId', $dp->id)
                ->update(['position' => $dp->position + 1]);
        }

        DB::table('development_plan_team_attributes')
            ->insert([
                'developmentPlanId' => $devPlan->id,
                'position'          => 0,
                'visible'           => false
            ]);

        return $devPlan;
    }

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
    public function scopeForTeams(Builder $query)
    {
        $query->join('development_plan_team_attributes', 'development_plans.id', '=', 'development_plan_team_attributes.developmentPlanId');
        return $query->orderBy('development_plan_team_attributes.position', 'ASC');
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
     * Returns the team attribute record for this model.
     * 
     * @return  object|null
     */
    public function teamAttribute()
    {
        return DB::table('development_plan_team_attributes')
                    ->where('developmentPlanId', $this->id)
                    ->first();
    }

    /**
     * Returns TRUE if this is a team development plan.
     * 
     * @return  boolean
     */
    public function isTeam()
    {
        return $this->teamAttribute() !== null;
    }

    /**
     * Updates team attributes.
     * 
     * @param   array  $attributes
     * @return  void
     */
    public function updateTeamAttribute(array $attributes)
    {
        DB::table('development_plan_team_attributes')
            ->where('developmentPlanId', $this->id)
            ->update($attributes);
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
    public function jsonSerialize($type = 0)
    {
        switch ($type) {
            case self::SERIALIZE_NORMAL:
                return $this->jsonSerializeNormal();
                break;
            case self::SERIALIZE_TEAM_DETAILS:
                return $this->jsonSerializeTeamDetailed();
                break;
        }
    }

    /**
     * Serialize as a normal development plan.
     * 
     * @return  array
     */
    public function jsonSerializeNormal()
    {
        $json = parent::jsonSerialize();
        $json['goals'] = $this->goals;
        return $json;
    }

    public function jsonSerializeTeamDetailed()
    {
        return [
            '_links'    => [
                'self'  => ['href' => route('api1-dev-plan-manager-teams.show', $this)],
                'goals' => ['href' => route('api1-dev-plan-goals.index', $this)]
            ],
            'id'        => $this->id,
            'name'      => $this->name,
            'ownerId'   => $this->ownerId,
            'position'  => $this->position,
            'checked'   => $this->checked,
            'visible'   => $this->attributes['visible'],
            'goals'     => $this->goals->map(function ($goal) {
                return [
                    'title'     => $goal->title,
                    'progress'  => $goal->calculateProgress(),
                    'owner'     => [
                        'id'    => $goal->owner->id,
                        'name'  => $goal->owner->name,
                    ],
                    'actions'   => $goal->actions
                ];
            })
        ];
    }
    
}
