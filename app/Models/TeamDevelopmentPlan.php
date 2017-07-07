<?php

namespace App\Models;

use App\Contracts\Routable;
use App\Http\JsonHalResponse;
use App\Scopes\PositionScope;
use Illuminate\Database\Eloquent\Model;

class TeamDevelopmentPlan extends Model implements Routable
{
    
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = ['position', 'visible'];

    /**
     * Moves up all of a user's development plans position by 1.
     * 
     * @param   App\Models\User  $owner
     * @return  void
     */
    public static function shift(User $owner)
    {
        $devPlans = $owner->teamDevelopmentPlans;
        foreach ($devPlans as $devPlan) {
            $devPlan->position = $devPlan->position + 1;
            $devPlan->save();
        }
    }

    /**
     * Re-sorts a user's team development plans.
     * 
     * @param   App\Models\User     $owner
     * @return  void
     */
    public static function sort(User $owner)
    {
        $owner->load('teamDevelopmentPlans');

        $devPlans = $owner->teamDevelopmentPlans;
        foreach ($devPlans as $index => $devPlan) {
            $devPlan->position = $index;
            $devPlan->save();
        }
    }

    /**
     * The "booting" method of the model.
     * 
     * @return  void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new PositionScope);
    }

    /**
     * Returns the URL to this development plan.
     * 
     * @return  string
     */
    public function url()
    {
        return route('api1-dev-plan-manager-teams.show', $this);
    }

    /**
     * Returns the owner of this team development plan.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\BelongsTo 
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'ownerId');
    }

    /**
     * Returns the category this team development plan belongs to.
     * 
     * @return  Illuminate\Database\Eloquent\Relations\HasOne  
     */
    public function category()
    {
        return $this->hasOne(QuestionCategory::class, 'id', 'categoryId');
    }

    public function goals()
    {
        $managedUsers = $this->owner->managedUsers->map(function ($user) {
            return $user->id;
        });

        return DevelopmentPlanGoal::where('categoryId', $this->categoryId)
            ->whereIn('ownerId', $managedUsers->toArray())
            ->get();
    }

    /**
     * Returns the JSON representation of this model.
     * 
     * @return  array
     */
    public function jsonSerialize($options = 0)
    {
        $json = [
            'id'        => $this->id,
            'name'      => $this->category->name,
            'ownerId'   => $this->ownerId,
            'visible'   => $this->attributes['visible'],
        ];

        if ($options == JsonHalResponse::SERIALIZE_FULL) {
            $json['goals'] = $this->goalsByUser();
        }

        return $json;
    }

    protected function goalsByUser()
    {
        $user2Goals = [];
        $result = [];

        foreach ($this->goals() as $goal) {
            if (!isset($user2Goals[$goal->owner->id])) {
                $user2Goals[$goal->owner->id] = [];
            }
            $user2Goals[$goal->owner->id][] = $goal;
        }

        foreach ($user2Goals as $userId => $goals) {
            $user = User::find($userId);
            $result[] = [
                'id'    => $user->id,
                'name'  => $user->name,
                'goals' => array_map(function($goal) {
                    return [
                        'id'        => $goal->id,
                        'title'     => $goal->title,
                        'progress'  => $goal->calculateProgress(),
                        'actions'   => $goal->actions
                    ];
                }, $goals),
            ];
        }

        return $result;
    }
}
