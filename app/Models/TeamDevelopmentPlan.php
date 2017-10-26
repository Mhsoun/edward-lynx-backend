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
     * Creates a new team development plan, creating a question category
     * for it if it doesn't exist yet.
     * 
     * @param   App\Models\User     $owner
     * @param   string              $name
     * @param   string              $lang
     * @return  App\Models\TeamDevelopmentPlan
     */
    public static function make(User $owner, $name, $lang)
    {
        $name = strip_tags($name);
        $category = QuestionCategory::where([
            'title'             => $name,
            'lang'              => $lang,
            'ownerId'           => $owner->id,
            'targetSurveyType'  => null,
            'isSurvey'          => false
        ])->first();

        // If no category with that name exists, create a new one.
        if (!$category) {
            $category = new QuestionCategory(['title' => $name]);
            $category->lang = $lang;
            $category->ownerId = $owner->id;
            $category->targetSurveyType = null;
            $category->save();
        }

        self::shift($owner);

        $devPlan = new self([
            'position'  => 0,
            'visible'   => false
        ]);
        $devPlan->ownerId = $owner->id;
        $devPlan->categoryId = $category->id;
        $devPlan->save();

        return $devPlan;
    }

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

    /**
     * Returns the goals attached to this team development plan.
     * 
     * @return  Illuminate\Database\Eloquent\Collection
     */
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
     * Calculate the progress of this development plan.
     * 
     * @return  float
     */
    public function calculateProgress()
    {
        $count = $this->goals()->count();
        if ($count == 0) {
            return 0;
        }

        $total = 0;
        foreach ($this->goals() as $goal) {
            $total += $goal->calculateProgress();
        }
        return $total / $count;
    }

    /**
     * Returns the JSON representation of this model.
     * 
     * @return  array
     */
    public function jsonSerialize($options = 0)
    {
        $progress = $this->calculateProgress();
        $checked = $progress == 1;

        $json = [
            'id'            => $this->id,
            'categoryId'    => $this->category->id,
            'ownerId'       => $this->ownerId,
            'name'          => $this->category->title,
            'visible'       => $this->attributes['visible'],
            'position'      => $this->position,
            'progress'      => $progress,
            'checked'       => $checked,
        ];

        if ($options == JsonHalResponse::SERIALIZE_FULL) {
            $json['goals'] = $this->goalsByUser();
        }

        return $json;
    }

    /**
     * Returns the goals sorted by user.
     * 
     * @return  array
     */
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
