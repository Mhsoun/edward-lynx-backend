<?php

namespace App\Observers;

use App\Models\DevelopmentPlanGoal;

class DevelopmentPlanGoalObserver
{

    public function updated(DevelopmentPlanGoal $goal)
    {
        $changed = $goal->getDirty();

        if (isset($changed['checked'])) {
            $goal->developmentPlan->updateChecked();
        }

        if (isset($changed['position'])) {
            $goal->developmentPlan->updateGoalPositions();
        }
    }

    public function deleted(DevelopmentPlanGoal $goal)
    {
        $goal->developmentPlan->updateChecked();
        $goal->developmentPlan->updateGoalPositions();
    }

}