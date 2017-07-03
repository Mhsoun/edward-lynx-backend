<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class DevelopmentPlanTeamAttribute extends Model
{

    public $timestamps = false;

    protected $fillable = ['position', 'visible'];
    
    /**
     * Returns the development plan linked to this attribute.
     * 
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function developmentPlan()
    {
        return $this->belongsTo(DevelopmentPlan::class, 'developmentPlanId');
    }

    /**
     * Set the position of a team development plan.
     * 
     * @param   integer     $position
     * @return  void
     */
    public function setPosition($position)
    {
        DB::table('development_plan_team_attributes')
            ->where('developmentPlanId', $this->developmentPlanId)
            ->update(['position' => $position]);
    }

}
