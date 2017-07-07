<?php

namespace App\Models;

use App\Scopes\PositionScope;
use Illuminate\Database\Eloquent\Model;

class TeamDevelopmentPlan extends Model
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
        return $this->hasOne(QuestionCategory::class, 'categoryId');
    }

}
