<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DevelopmentPlanTeamAttribute extends Model
{
    
    /**
     * Returns the development plan linked to this attribute.
     * 
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function developmentPlan()
    {
        return $this->belongsTo(DevelopmentPlan::class, 'developmentPlanId');
    }

}
