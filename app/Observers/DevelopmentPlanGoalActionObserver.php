<?php

namespace App\Observers;

use App\Models\DevelopmentPlanGoalAction;

class DevelopmentPlanGoalActionObserver
{

    public function updated(DevelopmentPlanGoalAction $action)
    {
        $changed = $action->getDirty();
        
        if (isset($changed['checked'])) {
            $action->goal->updateChecked();
        }

        if (isset($changed['position'])) {
            $action->goal->updateActionPositions();
        }
    }

    public function deleted(DevelopmentPlanGoalAction $action)
    {
        $action->goal->updateChecked();
        $action->goal->updateActionPositions();
    }

}