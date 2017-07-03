<?php

namespace App\Observers;

use App\Models\DevelopmentPlan;

class DevelopmentPlanObserver
{

    public function created(DevelopmentPlan $devPlan)
    {
        $this->updatePositions();
    }

    public function updated(DevelopmentPlan $devPlan)
    {
        $this->updatePositions();
    }

    public function deleted(DevelopmentPlan $devPlan)
    {
        $this->updatePositions();
    }

    protected function updatePositions()
    {
        DevelopmentPlan::sortTeamsByPosition($devPlan->owner);
    }

}